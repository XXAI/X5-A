<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaActas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->timestamp('fecha_entrega_fin')->after('fecha')->nullable();
            $table->timestamp('fecha_entrega_inicio')->after('fecha')->nullable();
            $table->timestamp('fecha_validacion')->after('fecha')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->dropColumn('fecha_validacion');
            $table->dropColumn('fecha_entrega_inicio');
            $table->dropColumn('fecha_entrega_fin');
        });
    }
}
