<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaListaBaseInusmosDetalle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lista_base_insumos_detalle', function (Blueprint $table) {
            $table->integer('lista_base_insumos_id')->length(10)->unsigned();
            $table->string('disur',50)->nullable();
            $table->string('exfarma',50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('lista_base_insumos_detalle');
    }
}
