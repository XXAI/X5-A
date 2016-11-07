<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModificarTablaRequisicionInsumoCluesAgregarCampoUsuario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requisicion_insumo_clues', function (Blueprint $table) {
            $table->integer('requisicion_id')->length(10)->unsigned()->nullable()->change();
            $table->string('usuario')->after('total_recibido')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requisicion_insumo_clues', function (Blueprint $table) {
            $table->integer('requisicion_id')->length(10)->unsigned()->change();
            $table->dropColumn('usuario');
        });
    }
}
