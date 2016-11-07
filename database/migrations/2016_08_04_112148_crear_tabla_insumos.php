<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaInsumos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('llave',50)->nullable();
            $table->string('proveedor',50)->nullable();
            $table->string('pedido',15)->nullable();
            $table->string('requisicion',10)->nullable();
            $table->integer('lote')->nullable();
            $table->string('clave',20)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('marca',255)->nullable();
            $table->string('unidad',255)->nullable();
            $table->integer('cantidad')->nullable();
            $table->decimal('precio',13,2)->nullable();
            $table->integer('tipo')->length(1)->nullable();
            $table->integer('cause')->length(1)->default(0);

            $table->index('clave'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('insumos');
    }
}