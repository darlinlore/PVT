<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_properties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('land_lot_number');//numero de lote de terreno
            $table->string('neighborhood_unit');//unidad vecinal
            $table->string('urbanization');//urbanización
            $table->string('surface');//superficie
            $table->string('measurement');//unidad de medida superficie
            $table->unsignedBigInteger('cadastral_code');//codigo catastral
            $table->string('limit');//colindancias
            $table->string('public_deed_number');//número de escritura publica
            $table->string('lawyer');//Notaria de fe publica
            $table->string('registration_number');//número de matricula computarizada
            $table->string('real_folio_number');//número de asiento del folio real
            $table->string('public_deed_date');//fecha de escritura publica
            $table->unsignedBigInteger('net_realizable_value');//valor neto realizable
            $table->unsignedBigInteger('​​real_right_city_id');// ciudad de registro en derechos reales
            $table->foreign('​​real_right_city_id')->references('id')->on('cities');
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
        Schema::dropIfExists('loan_properties');
    }
}
