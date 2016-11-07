<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPivoteRequisicionInsumo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requisicion_insumo', function (Blueprint $table) {
            $table->integer('requisicion_id')->length(10)->unsigned();
            $table->integer('insumo_id')->length(10)->unsigned();
            
            $table->integer('cantidad')->length(10);
            $table->decimal('total',15,2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('requisicion_insumo');
    }
}
