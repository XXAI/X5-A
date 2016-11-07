<?php

use Illuminate\Database\Seeder;
use \DB as DB;

class InsumosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$total = DB::table('insumos')->count();
        if($total == 0){
	        $csv = storage_path().'/app/seeds/insumos-data.csv';
			$query = sprintf("
				LOAD DATA local INFILE '%s' 
				INTO TABLE insumos 
				FIELDS TERMINATED BY ',' 
				OPTIONALLY ENCLOSED BY '\"' 
				ESCAPED BY '\"' 
				LINES TERMINATED BY '\\n' 
				IGNORE 1 LINES", addslashes($csv));
			DB::connection()->getpdo()->exec($query);
		}
    }
}
