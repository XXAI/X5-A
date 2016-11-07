<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPivoteRequisicionInsumoClues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requisicion_insumo_clues', function (Blueprint $table) {
            $table->integer('requisicion_id')->length(10)->unsigned();
            $table->integer('insumo_id')->length(10)->unsigned();
            $table->string('clues',12);

            $table->integer('cantidad')->length(10);
            $table->decimal('total',15,2);

            $table->integer('cantidad_validada')->length(10)->nullable();
            $table->decimal('total_validado',15,2)->nullable();

            $table->integer('cantidad_recibida')->length(10)->nullable();
            $table->decimal('total_recibido',15,2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('requisicion_insumo_clues');
    }
}
