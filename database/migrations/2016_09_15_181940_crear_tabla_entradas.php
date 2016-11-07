<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaEntradas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entradas', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('acta_id')->length(10)->unsigned();
            $table->integer('proveedor_id')->length(10)->unsigned();

            $table->date('fecha_recibe')->nullable();
            $table->time('hora_recibe')->nullable();
            $table->text('observaciones')->nullable();

            $table->string('nombre_recibe',225)->nullable();
            $table->string('nombre_entrega',225)->nullable();

            $table->integer('total_cantidad_recibida')->nullable();
            $table->integer('total_cantidad_validada')->nullable();
            $table->integer('total_claves_recibidas')->nullable();
            $table->integer('total_claves_validadas')->nullable();

            $table->decimal('porcentaje_cantidad',15,2)->nullable();
            $table->decimal('porcentaje_claves',15,2)->nullable();
            $table->integer('estatus')->length(1);
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
        Schema::drop('entradas');
    }
}
