<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaProveedores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre',255);
            $table->string('direccion',255)->nullable();
            $table->string('ciudad',255)->nullable();
            $table->string('contacto',255)->nullable();
            $table->string('cargo_contacto',255)->nullable();
            $table->string('telefono',20)->nullable();
            $table->string('cel',20)->nullable();
            $table->string('email',255)->nullable();
            $table->string('rfc',100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('proveedores');
    }
}
