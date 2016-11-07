<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateColumnRequisicionIdUnidad extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('requisicion_insumo_clues', 'requisicion_id_unidad')) {
            Schema::table('requisicion_insumo_clues', function (Blueprint $table) {
                $table->integer('requisicion_id_unidad')->length(11)->default(Null);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requisicion_insumo_clues', function (Blueprint $table) {
            $table->dropColumn('requisicion_id_unidad');
        });
    }
}
