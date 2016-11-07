<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use App\Models\Rol as Rol;
use App\Models\Usuario as Usuario;
use \Hash as Hash;

class UsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $total = DB::table('usuarios')->count();
        if($total == 0){
            $csv = storage_path().'/app/seeds/usuarios-clues.csv';
            $query = sprintf("
                LOAD DATA local INFILE '%s' 
                INTO TABLE usuarios 
                FIELDS TERMINATED BY ',' 
                OPTIONALLY ENCLOSED BY '\"' 
                ESCAPED BY '\"' 
                LINES TERMINATED BY '\\n' 
                IGNORE 1 LINES
                (id,password,clues,nombre,tipo_usuario,tipo_conexion)", addslashes($csv));
            DB::connection()->getpdo()->exec($query);
        }

        $total = DB::table('configuracion')->count();
        if($total == 0){
            $csv = storage_path().'/app/seeds/configuracion.csv';
            $query = sprintf("
                LOAD DATA local INFILE '%s' 
                INTO TABLE configuracion 
                FIELDS TERMINATED BY ',' 
                OPTIONALLY ENCLOSED BY '\"' 
                ESCAPED BY '\"' 
                LINES TERMINATED BY '\\n' 
                IGNORE 1 LINES
                (clues,clues_nombre,jurisdiccion,caravana_region,municipio,localidad,tipologia,empresa_clave,tipo_clues,empresa_nombre,director_unidad,administrador,encargado_almacen,coordinador_comision_abasto,lugar_entrega,lista_base_id)", addslashes($csv));
            DB::connection()->getpdo()->exec($query);
        }
    }
}