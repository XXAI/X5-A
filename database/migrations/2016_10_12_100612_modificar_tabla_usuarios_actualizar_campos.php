<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModificarTablaUsuariosActualizarCampos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('jurisdiccion');
            $table->dropColumn('municipio');
            $table->dropColumn('localidad');
            $table->dropColumn('tipologia');
            $table->dropColumn('empresa_clave');
            $table->dropColumn('director_unidad');
            $table->dropColumn('administrador');
            $table->dropColumn('encargado_almacen');
            $table->dropColumn('coordinador_comision_abasto');
            $table->dropColumn('lugar_entrega');

            $table->string('clues',15)->after('password')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('jurisdiccion',5)->nullable();
            $table->string('municipio',255)->nullable();
            $table->string('localidad',255)->nullable();
            $table->string('tipologia',255)->nullable();
            $table->string('empresa_clave',255)->nullable();

            $table->string('director_unidad',255)->nullable();
            $table->string('administrador',255)->nullable();
            $table->string('encargado_almacen',255)->nullable();
            $table->string('coordinador_comision_abasto',255)->nullable();
            $table->string('lugar_entrega',255)->nullable();
        });
    }
}
