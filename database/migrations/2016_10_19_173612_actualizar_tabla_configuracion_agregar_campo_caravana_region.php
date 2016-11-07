<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaConfiguracionAgregarCampoCaravanaRegion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('configuracion', 'caravana_region')) {
            Schema::table('configuracion', function (Blueprint $table) {
                $table->string('caravana_region',5)->after('jurisdiccion')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropColumn('caravana_region');
        });
    }
}
