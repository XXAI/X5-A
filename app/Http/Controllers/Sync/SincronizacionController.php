<?php

namespace App\Http\Controllers\Sync;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use \DB, \Storage, \ZipArchive, \Hash, \Response, \Config;
use App\Models\Sincronizacion, App\Models\Servidor; 
use App\Librerias\Sync\ArchivoSync;

class SincronizacionController extends \App\Http\Controllers\Controller
{
    
    /**
     * Crea un archivo comprimido para sincronización protegido con SECRET KEY 
     *
     * @return \Illuminate\Http\Response
     */
    public function manual()
    {

        // 1. Debemos generar el link de descarga en una carpeta con una cadena aleatoria
        // 2. Cuando generemos el link en el controlador debe existir un middleware que al descargar el archivo lo borre del sistema

        $ultima_sincronizacion =  Sincronizacion::select('fecha_generacion')->where("servidor_id",env("SERVIDOR_ID"))->orderBy('fecha_generacion','desc')->first();
        $fecha_generacion = date('Y-m-d H:i:s');

        Storage::delete("sync.".env('SERVIDOR_ID').".zip");
        Storage::makeDirectory("sync");
        
        // Creamos o reseteamos archivo de respaldo
        Storage::put('sync/header.sync',"ID=".env('SERVIDOR_ID'));
        Storage::append('sync/header.sync',"SECRET_KEY=".env('SECRET_KEY'));
        Storage::append('sync/header.sync',"VERSION=".env('VERSION'));
        Storage::append('sync/header.sync',"FECHA_SYNC=".$fecha_generacion);

        Storage::put('sync/sumami.sync', "");        
        Storage::append('sync/sumami.sync', "INSERT INTO sincronizaciones (servidor_id,fecha_generacion) VALUES ('".env('SERVIDOR_ID')."','".$fecha_generacion."'); \n");
        
        try {

            // Generamos archivo de sincronización de registros actualizados o creados a la fecha de corte
         
            foreach(Config::get("sync.tablas") as $key){
                
                if ($ultima_sincronizacion) {
                    $rows = DB::table($key)->where("servidor_id",env("SERVIDOR_ID"))->whereBetween('updated_at',[$ultima_sincronizacion->fecha_generacion,$fecha_generacion])->get();
                } else {             
                    $rows = DB::table($key)->where("servidor_id",env("SERVIDOR_ID"))->get();
                }
               
                if($rows){
                    Storage::append('sync/sumami.sync', "REPLACE INTO ".$key." VALUES ");
                    
                    $columnas = DB::getSchemaBuilder()->getColumnListing($key);
                    $index_replace = 0;
                       
                    foreach($rows as $row){
                        if ($index_replace!=0){
                            $item = ", (";
                        } else {
                            $item = "(";
                        }
                        
                        $index_items = 0;
                        foreach($columnas as $nombre){
                            if ($index_items!=0){
                                $item .= ",";
                            }

                            $tipo  = gettype($row->$nombre);
                            
                            switch($tipo){
                                case "string": $item .= "\"".$row->$nombre."\""; break;
                                case "NULL": $item .= "NULL"; break;
                                default: $item .= $row->$nombre;
                            }
                            
                            $index_items += 1;
                        }
                        $item .= ") ";
                        $index_replace += 1;
                        
                        Storage::append('sync/sumami.sync', $item);                       
                    }
                    Storage::append('sync/sumami.sync', "; \n");
                } 
            }
           
            // Generamos archivo de catalogos para que cuando se sincronize en el servidor principal se sepa si están actualizados o no
          
            if(Config::get("sync.catalogos")){   
                     
                $contador = 0;  
                
                foreach (Config::get("sync.catalogos") as $key) {
                   
                    $ultima_actualizacion = DB::table($key)->max("updated_at"); 
                    
                    if($contador==0){
                        Storage::put('sync/catalogos.sync', $key."=".$ultima_actualizacion);
                    } else {
                        Storage::append('sync/catalogos.sync', $key."=".$ultima_actualizacion);
                    }                    
                    $contador++;
                }
            } else {
                Storage::put('sync/catalogos.sync','');
            }
            $storage_path = storage_path();
            $zip = new ZipArchive();
            $zippath = $storage_path."/app/";
            $zipname = "sync.".env('SERVIDOR_ID').".zip";
           
            exec("zip -P ".env('SECRET_KEY')." -j -r ".$zippath.$zipname." \"".$zippath."/sync/\"");
            
            $zip_status = $zip->open($zippath.$zipname);

            if ($zip_status === true) {

                $zip->close();
                Storage::deleteDirectory("sync");
                
                ///Then download the zipped file.
                header('Content-Type: application/zip');
                header('Content-disposition: attachment; filename='.$zipname);
                header('Content-Length: ' . filesize($zippath.$zipname));
                readfile($zippath.$zipname);
                Storage::delete($zipname);
                exit();
            } else {                
                throw new \Exception("No se pudo crear el archivo");
            }
        } catch (\Exception $e) {    
            echo " Sync Manual Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Manual Excepción: ".$e->getMessage());  
            Storage::deleteDirectory("sync");        
        }
        
    }

    /**
     * Importa archivo comprimido para sincronización protegido con SECRET KEY y devuelve archivo de confirmacion
     *
     * @return \Illuminate\Http\Response
     */
    public function importarSync(Request $request)
    {
        DB::beginTransaction();
        try {
             
            Storage::makeDirectory("importar");
            if ($request->hasFile('sync')){
                $file = $request->file('sync');
                if ($file->isValid()) {
                    
                    Storage::put(
                        "importar/".$file->getClientOriginalName(),
                        file_get_contents($file->getRealPath())
                    );

                    $nombreArray = explode(".",$file->getClientOriginalName());

                    $servidor_id = $nombreArray[1];                    
                    $servidor = Servidor::find($servidor_id);                    

                    if($servidor){
                        $storage_path = storage_path();
                        $zip = new ZipArchive();
                        $zippath = $storage_path."/app/importar/";
                        $zipname = "sync.".$servidor_id.".zip";


                        $zip_status = $zip->open($zippath.$zipname) ;
            
            

                        if ($zip_status === true) {

                            if ($zip->setPassword($servidor->secret_key)){
                                Storage::makeDirectory("importar/".$servidor->id);
                                if ($zip->extractTo($zippath."/".$servidor->id)){
                                    $zip->close();
                                    //Borramos el ZIP y nos quedamos con los archivos extraidos
                                    Storage::delete("importar/".$file->getClientOriginalName());

                                    Storage::makeDirectory("importar/".$servidor->id."/confirmacion");
                                    
                                    //Obtenemos información del servidor que está sincronizando
                                    $contents_header = Storage::get("importar/".$servidor->id."/header.sync");
                                    $header_vars = ArchivoSync::parseVars($contents_header);

                                    // Obtenemos las fechas de actualizacion de sus catálogos
                                    $contents_catalogos = Storage::get("importar/".$servidor->id."/catalogos.sync");
                                    $catalogos_vars = ArchivoSync::parseVars($contents_catalogos);

                                   
                                    $actualizar_catalogos = "false";
                                    Storage::put("importar/".$servidor->id."/confirmacion/catalogos.sync","");
                                    foreach ($catalogos_vars as $key => $cat_ultima_actualizacion) {

                                        $principal_ultima_actualizacion = DB::table($key)->max("updated_at"); 
                                       
                                        if ($principal_ultima_actualizacion) {
                                            if ($principal_ultima_actualizacion != $cat_ultima_actualizacion) {
                                            
                                                $actualizar_catalogos = "true";
                                               
                                                $rows = DB::table($key)->whereBetween('updated_at',[$cat_ultima_actualizacion,$principal_ultima_actualizacion])->get();                                               
                                                 
                                                if($rows){
                                                    
                                                    Storage::append("importar/".$servidor->id."/confirmacion/catalogos.sync", "REPLACE INTO ".$key." VALUES ");
                                                    $columnas = DB::getSchemaBuilder()->getColumnListing($key);
                                                    $index_replace = 0;
                                                    foreach($rows as $row){
                                                        if ($index_replace!=0){
                                                            $item = ", (";
                                                        } else {
                                                            $item = "(";
                                                        }
                                                        
                                                        $index_items = 0;
                                                        foreach($columnas as $nombre){
                                                            if ($index_items!=0){
                                                                $item .= ",";
                                                            }

                                                            $tipo  = gettype($row->$nombre);
                                                            
                                                            switch($tipo){
                                                                case "string": $item .= "\"".$row->$nombre."\""; break;
                                                                case "NULL": $item .= "NULL"; break;
                                                                default: $item .= $row->$nombre;
                                                            }
                                                            
                                                            $index_items += 1;
                                                        }
                                                        $item .= ") ";
                                                        $index_replace += 1;
                                                        
                                                        Storage::append("importar/".$servidor->id."/confirmacion/catalogos.sync", $item);                       
                                                    }
                                                    Storage::append("importar/".$servidor->id."/confirmacion/catalogos.sync", "; \n");
                                                } 

                                            }
                                        }
                                    }
                              
                                    // Registramos la version del servidor
                                    $servidor->version = $header_vars['VERSION'];
                                    $servidor->save();

                                    // Comparamos la version del servidor principal y si es diferente le indicamos que tiene que actualizar
                                    if($servidor->version != env('VERSION')) {
                                        $actualizar_software = "true";
                                    } else {
                                        $actualizar_software = "false";
                                    }
                                    $contents = Storage::get("importar/".$servidor->id."/sumami.sync");
                                    DB::connection()->getpdo()->exec($contents);

                                    
                                    $confirmacion_file = "importar/".$servidor->id."/confirmacion/confirmacion.sync";
                                    Storage::put($confirmacion_file,"ID=".$servidor->id);
                                    Storage::append($confirmacion_file,"FECHA_SYNC=".$header_vars['FECHA_SYNC']);                                   
                                    Storage::append($confirmacion_file,"ACTUALIZAR_SOFTWARE=".$actualizar_software);
                                    Storage::append($confirmacion_file,"VERSION_ACTUAL_SOFTWARE=".env('VERSION'));
                                    Storage::append($confirmacion_file,"ACTUALIZAR_CATALOGOS=".$actualizar_catalogos);
                                    $storage_path = storage_path();
                                    
                                    $zip = new ZipArchive();
                                    $zippath = $storage_path."/app/";
                                    $zipname = "sync.confirmacion.".$servidor->id.".zip";
                                    

                                    exec("zip -P ".$servidor->secret_key." -j -r ".$zippath.$zipname." \"".$zippath."/importar/".$servidor->id."/confirmacion\"");
                                    $zip_status = $zip->open($zippath.$zipname) ;
                                    
                                    

                                    if ($zip_status === true) {

                                        $zip->close();  

                                        ///Then download the zipped file.
                                        header('Content-Type: application/zip');
                                        header('Content-disposition: attachment; filename='.$zipname);
                                        header('Content-Length: ' . filesize($zippath.$zipname));
                                        readfile($zippath.$zipname);
                                        Storage::delete($zipname);
                                        Storage::deleteDirectory("importar/".$servidor->id);
                                        exit();
                                    } else {            
                                        Storage::deleteDirectory("importar/".$servidor->id);    
                                        throw new \Exception("No se pudo crear el archivo");
                                    }
                                    


                                } else {
                                    Storage::delete("importar/".$file->getClientOriginalName());
                                    Storage::deleteDirectory("importar/".$servidor->id);
                                    throw new \Exception("No se pudo desencriptar el archivo, es posible que la llave de descriptación sea incorrecta, o que el nombre del archivo no corresponda al servidor correcto."); 
                                }

                            } else {
                                $zip->close();
                                Storage::delete("importar/".$file->getClientOriginalName());
                                throw new \Exception("Ocurrió un error al desencriptar el archivo");
                            }

                            
                            exit;
                        } else {   
                            Storage::delete("importar/".$file->getClientOriginalName());             
                            throw new \Exception("No se pudo leer el archivo");
                        }


                    } else{
                        Storage::delete("importar/".$file->getClientOriginalName());
                        throw new \Exception("Archivo inválido, es posible que el nombre haya sido alterado o el servidor que desea sincronizar no se encuentra registrado.");
                    }
                }
            } else {
                throw new \Exception("No hay archivo.");
            }
        } catch (\Illuminate\Database\QueryException $e){
            echo " Sync Importación Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Importación Excepción: ".$e->getMessage());
            DB::rollback();
        } 
        catch(\Exception $e ){
            return \Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }


    /**
     * Sincroniza la base de datos con servidor remoto
     *
     * @return \Illuminate\Http\Response
     */
    public function auto()
    {
        $ultima_sincronizacion =  Sincronizacion::select('fecha_generacion')->where("servidor_id",env("SERVIDOR_ID"))->orderBy('fecha_generacion','desc')->first();
        $fecha_generacion = date('Y-m-d H:i:s');
        
        try {
            $conexion_remota = DB::connection('mysql_sync');
            DB::beginTransaction();
            $conexion_remota->beginTransaction();
        } 
        catch (\Exception $e) {     
            Storage::append('log.sync', $fecha_generacion." Sync Auto Excepción: ".$e->getMessage());
            return " Sync Auto Excepción: ".$e->getMessage();            
        }
        
        try {
            
            foreach(Config::get("sync.tablas")as $key){
                
                if ($ultima_sincronizacion) {
                    $rows = DB::table($key)->where("servidor_id",env("SERVIDOR_ID"))->whereBetween('updated_at',[$ultima_sincronizacion->fecha_generacion,$fecha_generacion])->get();
                } else {             
                    $rows = DB::table($key)->where("servidor_id",env("SERVIDOR_ID"))->get();
                }                

                if($rows){                    
                  
                    $statement = "REPLACE INTO ".$key." VALUES ";                    
                    $columnas = DB::getSchemaBuilder()->getColumnListing($key);

                    $index_replace = 0;
                    foreach($rows as $row){
                        if ($index_replace!=0){
                            $item = ", (";
                        } else {
                            $item = "(";
                        }
                        
                        $index_items = 0;
                        foreach($columnas as $nombre){
                            if ($index_items!=0){
                                $item .= ",";
                            }

                            $tipo  = gettype($row->$nombre);
                            
                            switch($tipo){
                                case "string": $item .= "\"".$row->$nombre."\""; break;
                                case "NULL": $item .= "NULL"; break;
                                default: $item .= $row->$nombre;
                            }
                            
                            $index_items += 1;
                        }
                        $item .= ") ";
                        $index_replace += 1;
                        
                        $statement.= $item;                      
                    }
                    $statement .= ";";
                   
                    $conexion_remota->statement($statement);

                    echo "Tabla: ".$key."\t=> ".count($rows)." registros sincronizados \n";
                } else {
                    echo "Tabla: ".$key."\t=> 0 registros sincronizados \n";
                }
            }   

            $sincronizacion_remoto = new Sincronizacion;
            $sincronizacion_remoto->setConnection("mysql_sync");
            $sincronizacion_remoto->servidor_id = env('SERVIDOR_ID');
            $sincronizacion_remoto->fecha_generacion = $fecha_generacion;
            $sincronizacion_remoto->save();

            $sincronizacion = new Sincronizacion;
            $sincronizacion->servidor_id = env('SERVIDOR_ID');
            $sincronizacion->fecha_generacion = $fecha_generacion;
            $sincronizacion->save();

            DB::commit();
            $conexion_remota->commit();

        } catch (\Illuminate\Database\QueryException $e){
            echo " Sync Auto Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Auto Excepción: ".$e->getMessage());
            DB::rollback();
            $conexion_remota->rollback();
        }
        catch (\ErrorException $e) {
            echo " Sync Auto Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Auto Excepción: ".$e->getMessage());
            DB::rollback();
            $conexion_remota->rollback();
        } 
        catch (\Exception $e) {            
            echo " Sync Auto Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Auto Excepción: ".$e->getMessage());
            DB::rollback();
            $conexion_remota->rollback();
        }
    }

}
