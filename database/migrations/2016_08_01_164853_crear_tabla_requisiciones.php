<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaRequisiciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requisiciones', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('acta_id')->length(10)->unsigned();
            $table->integer('numero')->length(10)->nullable();
            $table->string('pedido',15);
            $table->string('lotes',255);
            $table->string('empresa',45);
            $table->integer('tipo_requisicion')->length(1);
            $table->integer('dias_surtimiento')->length(10);
            $table->decimal('sub_total',15,2);
            $table->decimal('gran_total',15,2);
            $table->decimal('iva',15,2);
            //$table->string('firma_solicita',255);
            //$table->string('cargo_solicita',255);
            //$table->string('firma_director',255)->nullable();
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
        Schema::drop('requisiciones');
    }
}
