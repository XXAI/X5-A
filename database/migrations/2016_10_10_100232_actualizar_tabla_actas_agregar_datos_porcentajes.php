<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaActasAgregarDatosPorcentajes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->integer('total_cantidad_recibida')->after('estatus_sincronizacion')->nullable();
            $table->integer('total_cantidad_validada')->after('estatus_sincronizacion')->nullable();
            $table->integer('total_claves_recibidas')->after('estatus_sincronizacion')->nullable();
            $table->integer('total_claves_validadas')->after('estatus_sincronizacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->dropColumn('total_cantidad_recibida');
            $table->dropColumn('total_cantidad_validada');
            $table->dropColumn('total_claves_recibidas');
            $table->dropColumn('total_claves_validadas');
        });
    }
}
