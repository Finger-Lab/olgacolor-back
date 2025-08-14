<?php

namespace Database\Seeders;

use App\Models\CurrencyRate;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencyRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar dados dos últimos 30 dias para USD
        $this->createUSDRates();
        
        // Criar dados dos últimos 30 dias para Alumínio
        $this->createAluminumRates();
    }
    
    /**
     * Criar cotações do USD dos últimos 30 dias
     */
    private function createUSDRates(): void
    {
        $baseRate = 5.20; // Taxa base fictícia
        
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            // Variação aleatória entre -3% e +3%
            $variation = (mt_rand(-300, 300) / 10000);
            $rate = $baseRate * (1 + $variation);
            
            CurrencyRate::create([
                'currency_type' => CurrencyRate::USD,
                'rate' => round($rate, 4),
                'rate_date' => $date
            ]);
            
            // Ajustar taxa base para próxima iteração (simular tendência)
            $baseRate = $rate;
        }
    }
    
    /**
     * Criar cotações do Alumínio dos últimos 30 dias
     */
    private function createAluminumRates(): void
    {
        $baseRate = 2100.00; // Taxa base fictícia por tonelada
        
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            // Variação aleatória entre -5% e +5%
            $variation = (mt_rand(-500, 500) / 10000);
            $rate = $baseRate * (1 + $variation);
            
            CurrencyRate::create([
                'currency_type' => CurrencyRate::ALUMINUM,
                'rate' => round($rate, 2),
                'rate_date' => $date
            ]);
            
            // Ajustar taxa base para próxima iteração
            $baseRate = $rate;
        }
    }
}
