<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaPivoteRequisicionInsumoAgregarProveedorId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requisicion_insumo', function (Blueprint $table) {
            $table->integer('proveedor_id')->length(10)->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requisicion_insumo', function (Blueprint $table) {
            $table->dropColumn('proveedor_id');
        });
    }
}
