<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModificarTablaActasAgregarCamposSincronizacion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->integer('estatus_sincronizacion')->after('estatus')->length(1)->default(0);
            $table->timestamp('sincronizado_termino')->after('estatus_sincronizacion')->nullable();
            $table->timestamp('sincronizado_validacion')->after('estatus_sincronizacion')->nullable();
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
            $table->dropColumn('estatus_sincronizacion');
            $table->dropColumn('sincronizado_termino');
            $table->dropColumn('sincronizado_validacion');
        });
    }
}
