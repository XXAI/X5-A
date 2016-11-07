<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaUsuarios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->string('id');
            $table->string('password',60);
            $table->string('nombre',255);
            $table->string('jurisdiccion',5);
            $table->string('municipio',255);
            $table->string('localidad',255);
            $table->string('tipologia',255);
            $table->string('empresa_clave',255);

            $table->string('director_unidad',255)->nullable();
            $table->string('administrador',255)->nullable();
            $table->string('encargado_almacen',255)->nullable();
            $table->string('coordinador_comision_abasto',255)->nullable();

            //$table->string('solicitante_nombre',255)->nullable();
            //$table->string('solicitante_cargo',255)->nullable();

            $table->string('lugar_entrega',255)->nullable();

            $table->timestamps();

            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('usuarios');
    }
}
