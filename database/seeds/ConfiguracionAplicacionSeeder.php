<?php

use Illuminate\Database\Seeder;

class ConfiguracionAplicacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $total = DB::table('configuracion_aplicacion')->count();
        if($total == 0){
            DB::table('configuracion_aplicacion')->insert([
    			[
    				'variable'			=> 'habilitar_captura',
    				'valor'				=> '1'
    			]
            ]);
        }
    }
}
