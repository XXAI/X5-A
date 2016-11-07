<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaRequisiciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requisiciones', function (Blueprint $table) {
            $table->integer('acta_id')->length(10)->unsigned()->nullable()->change();

            $table->string('clues',12)->nullable();

            $table->decimal('sub_total_validado',15,2)->nullable();
            $table->decimal('gran_total_validado',15,2)->nullable();
            $table->decimal('iva_validado',15,2)->nullable();

            $table->decimal('sub_total_recibido',15,2)->nullable();
            $table->decimal('gran_total_recibido',15,2)->nullable();
            $table->decimal('iva_recibido',15,2)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requisiciones', function (Blueprint $table) {
            $table->dropColumn('sub_total_validado');
            $table->dropColumn('gran_total_validado');
            $table->dropColumn('iva_validado');

            $table->dropColumn('sub_total_recibido');
            $table->dropColumn('gran_total_recibido');
            $table->dropColumn('iva_recibido');

            $table->dropColumn('clues');
        });
    }
}
