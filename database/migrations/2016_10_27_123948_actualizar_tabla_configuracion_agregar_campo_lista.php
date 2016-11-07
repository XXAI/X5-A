<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaConfiguracionAgregarCampoLista extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('configuracion', function (Blueprint $table) {
            $table->integer('lista_base_id')->length(10)->unsigned()->nullable()->after('lugar_entrega');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropColumn('lista_base_id');
        });
    }
}
