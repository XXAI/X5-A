<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use App\Models\Rol as Rol;
use App\Models\Permiso as Permiso;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $total = DB::table('roles')->count();
        if($total == 0){
        	$permisos = Permiso::lists('id');
            
        	$admin_rol = new Rol();
            $admin_rol->id = 'ADMIN';
            $admin_rol->nombre = 'Administrador';
            $admin_rol->save();

            $rol_id = $admin_rol->id;
            $relations = array();

            foreach ($permisos as $permiso_id) {
                $relations[] = [
                    'permiso_id' => $permiso_id,
                    'rol_id' => $rol_id
                ];
            }

            DB::table('permiso_rol')->insert($relations);

            $permisos = Permiso::where('grupo','=','Actas')->lists('id');
            
            $admin_rol = new Rol();
            $admin_rol->id = 'CAPTURA';
            $admin_rol->nombre = 'Captura de actas';
            $admin_rol->save();

            $rol_id = $admin_rol->id;
            $relations = array();

            foreach ($permisos as $permiso_id) {
                $relations[] = [
                    'permiso_id' => $permiso_id,
                    'rol_id' => $rol_id
                ];
            }

            DB::table('permiso_rol')->insert($relations);
        }
    }
}