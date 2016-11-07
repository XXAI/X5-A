<?php

use Illuminate\Database\Seeder;

class ListaBaseInsumoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$total = DB::table('lista_base_insumos')->count();
    	if($total == 0){
    		DB::table('lista_base_insumos')->insert([
				[
					'id' => '1',
					'nombre' => 'caravanas',
				]
			]);

	        $csv = storage_path().'/app/seeds/lista-base-insumos-data.csv';
			$query = sprintf("
				LOAD DATA local INFILE '%s' 
				INTO TABLE lista_base_insumos_detalle 
				FIELDS TERMINATED BY ',' 
				OPTIONALLY ENCLOSED BY '\"' 
				ESCAPED BY '\"' 
				LINES TERMINATED BY '\\n' 
				IGNORE 1 LINES", addslashes($csv));
			DB::connection()->getpdo()->exec($query);
    	}
    }
}
