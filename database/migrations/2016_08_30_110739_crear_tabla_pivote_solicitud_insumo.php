<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPivoteSolicitudInsumo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('solicitud_insumo', function (Blueprint $table) {
            $table->integer('solicitud_id')->length(10)->unsigned();
            $table->integer('insumo_id')->length(10)->unsigned();
            
            $table->integer('cantidad')->length(10);
            $table->decimal('total',15,2);

            $table->integer('cantidad_validada')->length(10)->nullable();
            $table->decimal('total_validado',15,2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('solicitud_insumo');
    }
}
