<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $total = DB::table('permisos')->count();
        if($total == 0){
            DB::table('permisos')->insert([
                [
                    'id' => '2EA4582FC8A19',
                    'descripcion' => "Ver usuarios",
                    'grupo' => "Administrador",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => '542C64323FC18',
                    'descripcion' => "Agregar usuarios",
                    'grupo' => "Administrador",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => 'DFBB15D35AF9F',
                    'descripcion' => "Editar usuarios",
                    'grupo' => "Administrador",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => 'A99BCC6596321',
                    'descripcion' => "Eliminar usuarios",
                    'grupo' => "Administrador",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => '37DC1A627A44E',
                    'descripcion' => "Editar Configuracion",
                    'grupo' => "Administrador",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => '71A3786CCEBD4',
                    'descripcion' => "Ver Configuracion",
                    'grupo' => "Administrador",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => 'D7A9BAC54EF15',
                    'descripcion' => "Ver roles",
                    'grupo' => "Administrador",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => 'AFE7E7583A18C',
                    'descripcion' => "Ver actas",
                    'grupo' => "Actas",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => '2EF18B5F2E2D7',
                    'descripcion' => "Agregar actas",
                    'grupo' => "Actas",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => 'AC634E145647F',
                    'descripcion' => "Editar actas",
                    'grupo' => "Actas",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => 'FF915DEC2F235',
                    'descripcion' => "Eliminar actas",
                    'grupo' => "Actas",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            ]);
        }
    }
}
