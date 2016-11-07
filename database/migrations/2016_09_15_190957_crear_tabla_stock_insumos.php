<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaStockInsumos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_insumos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entrada_id')->lenght(10);
            $table->string('clues',12)->nullable();
            $table->integer('insumo_id')->lenght(10);
            $table->string('lote',200);
            $table->date('fecha_caducidad')->nullable();
            $table->integer('cantidad_recibida');

            $table->integer('stock')->nullable();
            $table->integer('usado')->nullable();
            $table->integer('disponible')->nullable();

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
        Schema::drop('stock_insumos');
    }
}
