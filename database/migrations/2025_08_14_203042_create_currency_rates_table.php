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
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency_type'); // 'USD' ou 'ALUMINUM'
            $table->decimal('rate', 10, 4); // Valor da cotação
            $table->date('rate_date'); // Data da cotação
            $table->timestamps();
            
            // Índice único para evitar duplicatas na mesma data para o mesmo tipo
            $table->unique(['currency_type', 'rate_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
