<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Solicitud;
use App\Models\Configuracion;
use JWTAuth;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \PDF, \Storage, \ZipArchive;

class SolicitudController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        try{
            //DB::enableQueryLog();
            $usuario = JWTAuth::parseToken()->getPayload();

            $elementos_por_pagina = 50;
            $pagina = Input::get('pagina');
            if(!$pagina){
                $pagina = 1;
            }

            $query = Input::get('query');
            $filtro = Input::get('filtro');

            $recurso = Solicitud::where('folio','like',$usuario->get('clues').'/%');

            if($query){
                $recurso = $recurso->where(function($condition)use($query){
                    $condition->where('folio','LIKE','%'.$query.'%');
                });
            }

            $totales = $recurso->count();
            
            $recurso = $recurso->skip(($pagina-1)*$elementos_por_pagina)->take($elementos_por_pagina)
                                ->orderBy('id','desc')->get();

            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            return Response::json(['data'=>$recurso,'totales'=>$totales],200);
        }catch(Exception $ex){
            return Response::json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        $mensajes = [
            'required'      => "required",
            'array'         => "array",
            'min'           => "min",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas_acta = [
            'ciudad'            =>'required',
            'fecha'             =>'required',
            'hora_inicio'       =>'required',
            'hora_termino'      =>'required',
            'lugar_reunion'     =>'required',
            //'firma_solicita'    =>'required',
            //'cargo_solicita'    =>'required',
            'requisiciones'     =>'required|array|min:1'
        ];

        $reglas_requisicion = [
            'acta_id'           =>'required',
            'pedido'            =>'required',
            'lotes'             =>'required',
            'tipo_requisicion'  =>'required',
            'dias_surtimiento'  =>'required',
            'sub_total'         =>'required',
            'gran_total'        =>'required',
            'iva'               =>'required'
            //'firma_solicita'    =>'required',
            //'cargo_solicita'    =>'required'
        ];

        $inputs = Input::all();
        //$inputs = Input::only('id','servidor_id','password','nombre', 'apellidos');
        //var_dump(json_encode($inputs));die;

        $v = Validator::make($inputs, $reglas_acta, $mensajes);
        if ($v->fails()) {
            return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
        }

        try {

            DB::beginTransaction();

            //$max_acta = Acta::max('id');
            $usuario = JWTAuth::parseToken()->getPayload();
            $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

            $inputs['folio'] = $configuracion->clues . '/'.'00'.'/' . date('Y');
            $inputs['estatus'] = 1;
            $inputs['empresa'] = $configuracion->empresa_clave;
            $inputs['lugar_entrega'] = $configuracion->lugar_entrega;
            $inputs['director_unidad'] = $configuracion->director_unidad;
            $inputs['administrador'] = $configuracion->administrador;
            $inputs['encargado_almacen'] = $configuracion->encargado_almacen;
            $inputs['coordinador_comision_abasto'] = $configuracion->coordinador_comision_abasto;
            $acta = Acta::create($inputs);

            if(isset($inputs['requisiciones'])){
                if(count($inputs['requisiciones']) > 3){
                    DB::rollBack();
                    throw new \Exception("No pueden haber mas de tres requesiciones por acta");
                }

                foreach ($inputs['requisiciones'] as $inputs_requisicion) {
                    $inputs_requisicion['acta_id'] = $acta->id;
                    //$inputs_requisicion['firma_director'] = $configuracion->director_unidad;
                    $v = Validator::make($inputs_requisicion, $reglas_requisicion, $mensajes);
                    if ($v->fails()) {
                        DB::rollBack();
                        return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
                    }

                    //$max_requisicion = Requisicion::max('numero');
                    //if(!$max_requisicion){
                        //$max_requisicion = 0;
                    //}
                    //$inputs_requisicion['numero'] = $max_requisicion+1;
                    $inputs_requisicion['empresa'] = $configuracion->empresa_clave;
                    $inputs_requisicion['dias_surtimiento'] = 15;
                    $inputs_requisicion['sub_total'] = 0;
                    $inputs_requisicion['iva'] = 0;
                    $inputs_requisicion['gran_total'] = 0;
                    $requisicion = Requisicion::create($inputs_requisicion);

                    if(isset($inputs_requisicion['insumos'])){
                        $insumos = [];
                        //$suma = 0;
                        //$iva = 0;
                        foreach ($inputs_requisicion['insumos'] as $req_insumo) {
                            $insumos[] = [
                                'insumo_id' => $req_insumo['insumo_id'],
                                'cantidad' => $req_insumo['cantidad'],
                                'total' => $req_insumo['total']
                            ];
                        }
                        $requisicion->insumos()->sync($insumos);

                        $sub_total = $requisicion->insumos()->sum('total');
                        $requisicion->sub_total = $sub_total;
                        if($requisicion->tipo_requisicion == 3){
                            $requisicion->iva = $sub_total*16/100;
                        }else{
                            $requisicion->iva = 0;
                        }
                        $requisicion->gran_total = $sub_total + $requisicion->iva;
                        $requisicion->save();
                    }
                }
            }

            DB::commit();

            return Response::json([ 'data' => $acta ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id){
        return Response::json([ 'data' => Acta::with('requisiciones.insumos')->find($id) ],200);
    }

    public function generarActaPDF($id){
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $data = [];
        $data['acta'] = Acta::find($id);

        if($data['acta']->estatus > 2){
            $data['acta']->load(['requisiciones'=>function($query){
                $query->where('gran_total_validado','>',0);
            }]);
        }else{
            $data['acta']->load('requisiciones');
        }
        
        $usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

        /*if($data['acta']->estatus != 2){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra finalizada'], HttpResponse::HTTP_CONFLICT);
        }*/

        $pedidos = $data['acta']->requisiciones->lists('pedido')->toArray();
        if(count($pedidos) == 1){
            $data['acta']->requisiciones = $pedidos[0];
        }elseif(count($pedidos) == 2){
            $data['acta']->requisiciones = $pedidos[0] . ' y ' . $pedidos[1];
        }else{
            $data['acta']->requisiciones = $pedidos[0] . ', ' . $pedidos[1] . ' y ' . $pedidos[2];
        }

        $data['acta']->hora_inicio = substr($data['acta']->hora_inicio, 0,5);
        $data['acta']->hora_termino = substr($data['acta']->hora_termino, 0,5);

        $fecha = explode('-',$data['acta']->fecha);
        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha = $fecha;

        $data['unidad'] = $configuracion->clues_nombre;
        $data['empresa'] = $configuracion->empresa_nombre;
        $data['empresa_clave'] = $configuracion->empresa_clave;
        
        $pdf = PDF::loadView('pdf.acta', $data);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(($w/2)-10, ($h-100), "{PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0, 0, 0));
        
        return $pdf->stream($data['acta']->folio.'-Acta.pdf');
    }

    public function generarRequisicionPDF($id){
        $meses = ['01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'];
        $data = [];
        $data['acta'] = Acta::find($id);

        if($data['acta']->estatus > 2){
            $data['acta']->load([
                'requisiciones'=>function($query){
                    $query->where('gran_total_validado','>',0);
                },'requisiciones.insumos'=>function($query){
                    $query->wherePivot('total_validado','>',0);
                }
            ]);
        }else{
            $data['acta']->load('requisiciones.insumos');
        }

        $usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

        $fecha = explode('-',$data['acta']->fecha);
        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha = $fecha;

        /*if($data['acta']->estatus != 2){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra finalizada'], HttpResponse::HTTP_CONFLICT);
        }*/

        $data['unidad'] = mb_strtoupper($configuracion->clues_nombre,'UTF-8');
        $data['empresa'] = $configuracion->empresa_nombre;
        $data['empresa_clave'] = $configuracion->empresa_clave;

        $pdf = PDF::loadView('pdf.requisiciones', $data);
        return $pdf->stream($data['acta']->folio.'Requisiciones.pdf');
    }

    function encryptData($value){
       $key = "1C6B37CFCDF98AB8FA29E47E4B8EF1F3";
       $text = $value;
       $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
       $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
       $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv);
       return $crypttext;
    }

    function decryptData($value){
       $key = "1C6B37CFCDF98AB8FA29E47E4B8EF1F3";
       $crypttext = $value;
       $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
       $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
       $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $crypttext, MCRYPT_MODE_ECB, $iv);
       return trim($decrypttext);
    } 

    public function generarJSON($id){
        $acta = Acta::with('requisiciones.insumos','requisiciones.insumosClues')->find($id);

        if($acta->estatus < 2){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra finalizada'], HttpResponse::HTTP_CONFLICT);
        }

        Storage::makeDirectory("export");
        Storage::put('export/json.'.str_replace('/','-', $acta->folio),json_encode($acta));

        $filename = storage_path()."/app/export/json.".str_replace('/','-', $acta->folio);
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        $EncryptedData=$this->encryptData($contents);
        Storage::put('export/json.'.str_replace('/','-', $acta->folio),$EncryptedData);
        fclose($handle);

        $storage_path = storage_path();

        $zip = new ZipArchive();
        $zippath = $storage_path."/app/";
        $zipname = "acta.".str_replace('/','-', $acta->folio).".zip";

        $zip_status = $zip->open($zippath.$zipname,ZIPARCHIVE::CREATE);

        if ($zip_status === true) {
            $zip->addFile(storage_path().'/app/export/json.'.str_replace('/','-', $acta->folio),'acta.json');
            $zip->close();
            Storage::deleteDirectory("export");
            
            ///Then download the zipped file.
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename='.$zipname);
            header('Content-Length: ' . filesize($zippath.$zipname));
            readfile($zippath.$zipname);
            Storage::delete($zipname);
            exit();
        }else{
            return Response::json(['error' => 'El archivo zip, no se encuentra'], HttpResponse::HTTP_CONFLICT);
        }
    }

    public function actualizarActa(Request $request){
        $mensajes = [
            'required'      => "required",
            'array'         => "array",
            'min'           => "min",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas_acta = [
            'folio'                         => 'required',
            'fecha_validacion'              => 'required',
            'estatus'                       => 'required',
            'clues'                         => 'required',
            'requisiciones'                 => 'required|array|min:1'
        ];

        $reglas_requisicion = [
            'tipo_requisicion'      =>'required',
            'estatus'               =>'required',
            'sub_total_validado'    =>'required',
            'gran_total_validado'   =>'required',
            'iva_validado'          =>'required'
        ];

        $usuario = JWTAuth::parseToken()->getPayload();
        $usuario_id = $usuario->get('id');

        try {
            if(Input::hasFile('zipfile')){
                $destinationPath = storage_path().'/app/imports/'.$usuario_id.'/';
                $upload_success = Input::file('zipfile')->move($destinationPath, 'archivo_zip.zip');

                $zip = new ZipArchive;
                $res = $zip->open($destinationPath.'archivo_zip.zip');
                if ($res === TRUE) {
                    $zip->extractTo($destinationPath);
                    $zip->close();
                } else {
                    return Response::json(['error' => 'No se pudo extraer el archivo'], HttpResponse::HTTP_CONFLICT);
                }
                
                $filename = $destinationPath . 'acta.json';
                $handle = fopen($filename, "r");
                $contents = fread($handle, filesize($filename));
                $DecryptedData=$this->decryptData($contents);
                fclose($handle);
                
                //$str = file_get_contents($destinationPath.'acta.json');
                $json = json_decode($DecryptedData, true);

                $v = Validator::make($json, $reglas_acta, $mensajes);
                if ($v->fails()) {
                    Storage::deleteDirectory('imports/'.$usuario_id.'/');
                    return Response::json(['error' => 'Los datos del acta en el archivo estan incompletos', 'error_type'=>'data_validation','fields'=>$v->errors()], HttpResponse::HTTP_CONFLICT);
                }

                DB::beginTransaction();

                $acta = Acta::with('requisiciones.insumos','requisiciones.insumosClues')
                            ->where('folio',$json['folio'])->where('folio','like',$usuario_id.'/%')->first();

                if(!$acta){
                    Storage::deleteDirectory('imports/'.$usuario_id.'/');
                    return Response::json(['error' =>'El Acta indicada en el archivo no fue encontrada.', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
                }

                if($acta->estatus < 2){
                    Storage::deleteDirectory('imports/'.$usuario_id.'/');
                    return Response::json(['error' =>'El Acta no se encuentra finalizada.', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
                }

                $acta->fecha_validacion = $json['fecha_validacion'];
                $acta->estatus = $json['estatus'];

                $acta->save();

                $requisiciones_json = [];
                foreach ($json['requisiciones'] as $inputs_requisicion) {
                    $v = Validator::make($inputs_requisicion, $reglas_requisicion, $mensajes);
                    if ($v->fails()) {
                        DB::rollBack();
                        return Response::json(['error' => 'Los datos de las requisiciones en el archivo estan incompletos', 'error_type' => 'data_validation','fields'=>$v->errors()], HttpResponse::HTTP_CONFLICT);
                    }

                    if(isset($inputs_requisicion['insumos'])){
                        $insumos = [];
                        foreach ($inputs_requisicion['insumos'] as $req_insumo) {
                            $insumos[$req_insumo['llave']] = [
                                'cantidad_validada' => $req_insumo['pivot']['cantidad_validada'],
                                'total_validado' => $req_insumo['pivot']['total_validado']
                            ];
                        }
                        $inputs_requisicion['insumos'] = $insumos;
                    }

                    if(isset($inputs_requisicion['insumos_clues'])){
                        $insumos = [];
                        foreach ($inputs_requisicion['insumos_clues'] as $req_insumo) {
                            $insumos[$req_insumo['llave'].'.'.$req_insumo['pivot']['clues']] = [
                                'llave' => $req_insumo['llave'],
                                'clues' => $req_insumo['pivot']['clues'],
                                'cantidad_validada' => $req_insumo['pivot']['cantidad_validada'],
                                'total_validado' => $req_insumo['pivot']['total_validado']
                            ];
                        }
                        $inputs_requisicion['insumos_clues'] = $insumos;
                    }

                    if(!isset($requisiciones_json[$inputs_requisicion['tipo_requisicion']])){
                        $requisiciones_json[$inputs_requisicion['tipo_requisicion']] = $inputs_requisicion;
                    }
                }

                if(count($requisiciones_json) > 4){
                    return Response::json(['error' => 'No debe de haber mas de cuatro requisiciones', 'error_type' => 'data_validation'], HttpResponse::HTTP_CONFLICT);
                }

                foreach ($acta->requisiciones as $requisicion) {
                    if(isset($requisiciones_json[$requisicion->tipo_requisicion])){
                        $requisicion_import = $requisiciones_json[$requisicion->tipo_requisicion];
                        $requisicion->numero = $requisicion_import['numero'];
                        $requisicion->sub_total_validado = $requisicion_import['sub_total_validado'];
                        $requisicion->gran_total_validado = $requisicion_import['gran_total_validado'];
                        $requisicion->iva_validado = $requisicion_import['iva_validado'];

                        $requisicion->save();

                        $insumos_sync = [];
                        foreach ($requisicion->insumos as $insumo) {
                            $nuevo_insumo = [
                                'insumo_id' => $insumo->id,
                                'cantidad' => $insumo->pivot->cantidad,
                                'total' => $insumo->pivot->total
                            ];
                            if(isset($requisicion_import['insumos'][$insumo->llave])){
                                $insumo_import = $requisicion_import['insumos'][$insumo->llave];
                                $nuevo_insumo['total_validado'] = $insumo_import['total_validado'];
                                $nuevo_insumo['cantidad_validada'] = $insumo_import['cantidad_validada'];
                            }
                            $insumos_sync[] = $nuevo_insumo;
                        }
                        $requisicion->insumos()->sync([]);
                        $requisicion->insumos()->sync($insumos_sync);

                        $insumos_clues_sync = [];
                        foreach ($requisicion->insumosClues as $insumo) {
                            $nuevo_insumo = [
                                'insumo_id' => $insumo->id,
                                'clues' => $insumo->pivot->clues,
                                'cantidad' => $insumo->pivot->cantidad,
                                'total' => $insumo->pivot->total
                            ];
                            if(isset($requisicion_import['insumos_clues'][$insumo->llave.'.'.$insumo->pivot->clues])){
                                $insumo_import = $requisicion_import['insumos_clues'][$insumo->llave.'.'.$insumo->pivot->clues];
                                $nuevo_insumo['total_validado'] = $insumo_import['total_validado'];
                                $nuevo_insumo['cantidad_validada'] = $insumo_import['cantidad_validada'];
                            }
                            $insumos_clues_sync[] = $nuevo_insumo;
                        }
                        $requisicion->insumosClues()->sync([]);
                        $requisicion->insumosClues()->sync($insumos_clues_sync);
                    }
                }

                DB::commit();

                Storage::deleteDirectory('imports/'.$usuario_id.'/');

                return Response::json([ 'data' => $json ],200);
            }else{
                throw new \Exception("No se encontro archivo a subir.", 1);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Storage::deleteDirectory('imports/'.$usuario_id.'/');
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id){
        $mensajes = [
            'required'      => "required",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas_configuracion = [
            'director_unidad'               => 'required',
            'administrador'                 => 'required',
            'encargado_almacen'             => 'required',
            'coordinador_comision_abasto'   => 'required',
            'lugar_entrega'                 => 'required'
        ];

        $reglas_acta = [
            'ciudad'            =>'required',
            'fecha'             =>'required',
            'hora_inicio'       =>'required',
            'hora_termino'      =>'required',
            'lugar_reunion'     =>'required',
            //'firma_solicita'    =>'required',
            //'cargo_solicita'    =>'required',
            'requisiciones'     =>'required|array|min:1'
        ];

        $reglas_requisicion = [
            'pedido'            =>'required',
            'lotes'             =>'required',
            'tipo_requisicion'  =>'required',
            'dias_surtimiento'  =>'required',
            'sub_total'         =>'required',
            'gran_total'        =>'required',
            'iva'               =>'required'
            //'firma_solicita'    =>'required',
            //'cargo_solicita'    =>'required'
        ];

        $usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('id'))->first();

        $inputs = Input::all();
        //$inputs = Input::only('id','servidor_id','password','nombre', 'apellidos');
        //var_dump(json_encode($inputs));die;

        $v = Validator::make($inputs, $reglas_acta, $mensajes);
        if ($v->fails()) {
            return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
        }

        try {

            DB::beginTransaction();

            $acta = Acta::find($id);

            if($acta->estatus >= 2){
                throw new \Exception("El Acta no se puede editar ya que se encuentra con estatus de finalizada");
            }

            $inputs['lugar_entrega'] = $configuracion->lugar_entrega;
            $inputs['director_unidad'] = $configuracion->director_unidad;
            $inputs['administrador'] = $configuracion->administrador;
            $inputs['encargado_almacen'] = $configuracion->encargado_almacen;
            $inputs['coordinador_comision_abasto'] = $configuracion->coordinador_comision_abasto;

            if($inputs['estatus'] == 2 && $acta->estatus != 2){
                $max_acta = Acta::where('folio','like',$configuracion->clues.'/%')->max('numero');
                if(!$max_acta){
                    $max_acta = 0;
                }
                $inputs['folio'] = $configuracion->clues . '/'.($max_acta+1).'/' . date('Y');
                $inputs['numero'] = ($max_acta+1);

                $v = Validator::make($inputs, $reglas_configuracion, $mensajes);
                if ($v->fails()) {
                    DB::rollBack();
                    return Response::json(['error' => 'Faltan datos de Configuración por capturar.', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
                }
            }

            $acta->update($inputs);

            if(isset($inputs['requisiciones'])){
                if(count($inputs['requisiciones']) > 3){
                    throw new \Exception("No pueden haber mas de tres requesiciones por acta");
                }

                $acta->load('requisiciones');
                $requisiciones_guardadas = [];
                foreach ($inputs['requisiciones'] as $inputs_requisicion) {
                    $inputs_requisicion['dias_surtimiento'] = 15;
                    //$inputs_requisicion['firma_director'] = $configuracion->director_unidad;
                    $v = Validator::make($inputs_requisicion, $reglas_requisicion, $mensajes);
                    if ($v->fails()) {
                        DB::rollBack();
                        return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
                    }

                    /*
                    if($acta->estatus == 2 && !isset($inputs_requisicion['numero'])){
                        $actas = Acta::where('folio','like',$configuracion->clues.'/%')->lists('id');
                        $max_requisicion = Requisicion::whereIn('acta_id',$actas)->max('numero');
                        if(!$max_requisicion){
                            $max_requisicion = 0;
                        }
                        $inputs_requisicion['numero'] = $max_requisicion+1;
                    }
                    */

                    if(isset($inputs_requisicion['id'])){
                        $requisicion = Requisicion::find($inputs_requisicion['id']);
                        $requisicion->update($inputs_requisicion);
                        $requisiciones_guardadas[$requisicion->id] = true;
                    }else{
                        $inputs_requisicion['acta_id'] = $acta->id;
                        $inputs_requisicion['empresa'] = $configuracion->empresa_clave;
                        $requisicion = Requisicion::create($inputs_requisicion);
                    }

                    if(isset($inputs_requisicion['insumos'])){
                        $insumos = [];
                        foreach ($inputs_requisicion['insumos'] as $req_insumo) {
                            $insumos[] = [
                                'insumo_id' => $req_insumo['insumo_id'],
                                'cantidad' => $req_insumo['cantidad'],
                                'total' => $req_insumo['total']
                            ];
                        }
                        $requisicion->insumos()->sync([]);
                        $requisicion->insumos()->sync($insumos);

                        $sub_total = $requisicion->insumos()->sum('total');
                        $requisicion->sub_total = $sub_total;
                        if($requisicion->tipo_requisicion == 3){
                            $requisicion->iva = $sub_total*16/100;
                        }else{
                            $requisicion->iva = 0;
                        }
                        $requisicion->gran_total = $sub_total + $requisicion->iva;
                        $requisicion->save();
                    }else{
                        $requisicion->insumos()->sync([]);
                        $requisicion->sub_total = 0;
                        $requisicion->iva = 0;
                        $requisicion->gran_total = 0;
                        $requisicion->save();

                    }
                }
                $eliminar_requisiciones = [];
                foreach ($acta->requisiciones as $requisicion) {
                    if(!isset($requisiciones_guardadas[$requisicion->id])){
                        $eliminar_requisiciones[] = $requisicion->id;
                        $requisicion->insumos()->sync([]);
                    }
                }
                if(count($eliminar_requisiciones)){
                    Requisicion::whereIn('id',$eliminar_requisiciones)->delete();
                }
            }

            DB::commit();
            $acta->load('requisiciones.insumos');
            return Response::json([ 'data' => $acta, 'respuesta_code' =>'updated' ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        try {
            $acta = Acta::with('requisiciones')->find($id);
            foreach ($acta->requisiciones as $requisicion) {
                $requisicion->insumos()->sync([]);
            }
            Requisicion::where('acta_id',$id)->delete();
            Acta::destroy($id);
            return Response::json(['data'=>'Elemento eliminado con exito'],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
