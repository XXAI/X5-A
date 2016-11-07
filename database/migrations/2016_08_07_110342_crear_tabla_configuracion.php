<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaConfiguracion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->increments('id');
            $table->string('clues',15);
            $table->string('clues_nombre',255);

            $table->string('jurisdiccion',5)->nullable();
            $table->string('municipio',255)->nullable();
            $table->string('localidad',255)->nullable();
            $table->string('tipologia',255)->nullable();

            $table->string('empresa_clave',255)->nullable();
            $table->string('empresa_nombre',255)->nullable();

            $table->string('director_unidad',255)->nullable();
            $table->string('administrador',255)->nullable();
            $table->string('encargado_almacen',255)->nullable();
            $table->string('coordinador_comision_abasto',255)->nullable();

            //$table->string('solicitante_nombre',255)->nullable();
            //$table->string('solicitante_cargo',255)->nullable();

            $table->string('lugar_entrega',255)->nullable();

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
        Schema::drop('configuracion');
    }
}
