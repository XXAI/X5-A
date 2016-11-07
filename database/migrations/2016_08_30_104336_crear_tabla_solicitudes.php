<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaSolicitudes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('folio',100);
            $table->integer('numero')->length(10);
            $table->date('fecha');
            $table->string('empresa',45);

            $table->integer('cantidad')->length(10)->default(0);
            $table->decimal('sub_total',15,2)->default(0);
            $table->decimal('iva',15,2)->default(0);
            $table->decimal('gran_total',15,2)->default(0);

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
        Schema::drop('solicitudes');
    }
}
