<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPivotePermisoRol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permiso_rol', function (Blueprint $table) {

			$table->string('permiso_id',32);
			$table->string('rol_id');

			$table->foreign('permiso_id')
                  ->references('id')->on('permisos')
                  ->onDelete('cascade');

			$table->foreign('rol_id')
                  ->references('id')->on('roles')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('permiso_rol');
    }
}
