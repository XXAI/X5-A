<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaPivoteRequisicionInsumo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requisicion_insumo', function (Blueprint $table) {
            $table->integer('cantidad_validada')->length(10)->nullable();
            $table->decimal('total_validado',15,2)->nullable();

            $table->integer('cantidad_recibida')->length(10)->nullable();
            $table->decimal('total_recibido',15,2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requisicion_insumo', function (Blueprint $table) {
            $table->dropColumn('cantidad_validada');
            $table->dropColumn('total_validado');

            $table->dropColumn('cantidad_recibida');
            $table->dropColumn('total_recibido');
        });
    }
}
