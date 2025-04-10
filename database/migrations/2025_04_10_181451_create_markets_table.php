<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->double('air_permeability');
            $table->double('water_tightness');
            $table->double('wind_resistance');
            $table->double('acoustic_insulation');
            $table->double('thermal_transmittance');
            $table->double('glazing_thickness');
            $table->double('width');
            $table->double('height');
            $table->double('weight');
            $table->double('theoretical_thickness');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
