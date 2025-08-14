<?php

namespace App\Services;

use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CurrencyRateService
{
    /**
     * Buscar e armazenar cotação do USD
     */
    public function fetchAndStoreUSDRate(): bool
    {
        try {
            // Usando API gratuita do exchangerate-api.com
            // Você pode se registrar para obter uma chave gratuita
            $response = Http::get('https://api.exchangerate-api.com/v4/latest/USD');
            
            if ($response->successful()) {
                $data = $response->json();
                $brlRate = $data['rates']['BRL'] ?? null;
                
                if ($brlRate) {
                    // Inverter para obter USD em BRL
                    $usdRate = 1 / $brlRate;
                    
                    $this->storeCurrencyRate(CurrencyRate::USD, $usdRate);
                    Log::info('Cotação USD atualizada', ['rate' => $usdRate]);
                    return true;
                }
            }
            
            // Fallback: usar API do Banco Central do Brasil
            return $this->fetchUSDFromBCB();
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar cotação USD', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Fallback para buscar USD do Banco Central do Brasil
     */
    private function fetchUSDFromBCB(): bool
    {
        try {
            $today = Carbon::today()->format('m-d-Y');
            $response = Http::get("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoMoedaDia(moeda=@moeda,dataCotacao=@dataCotacao)", [
                'moeda' => "'USD'",
                'dataCotacao' => "'{$today}'",
                '$format' => 'json'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['value'])) {
                    $rate = $data['value'][0]['cotacaoCompra'];
                    $this->storeCurrencyRate(CurrencyRate::USD, $rate);
                    Log::info('Cotação USD (BCB) atualizada', ['rate' => $rate]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar cotação USD do BCB', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Buscar e armazenar cotação do Alumínio
     */
    public function fetchAndStoreAluminumRate(): bool
    {
        try {
            // Usando API fictícia do London Metal Exchange (LME)
            // Nota: Para uso real, você precisa se registrar em uma API de commodities
            // como MetalsAPI.com, Alpha Vantage, ou similar
            
            // Simulando um valor para demonstração
            // Em produção, substitua por uma chamada real de API
            $mockRate = $this->getMockAluminumRate();
            
            $this->storeCurrencyRate(CurrencyRate::ALUMINUM, $mockRate);
            Log::info('Cotação Alumínio atualizada (mock)', ['rate' => $mockRate]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar cotação Alumínio', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Método para integração real com API de commodities
     * Exemplo usando MetalsAPI (requer chave de API)
     */
    private function fetchAluminumFromMetalsAPI(): bool
    {
        try {
            $apiKey = env('METALS_API_KEY'); // Adicionar no .env
            
            if (!$apiKey) {
                Log::warning('METALS_API_KEY não configurada');
                return false;
            }

            $response = Http::get('https://metals-api.com/api/latest', [
                'access_key' => $apiKey,
                'base' => 'USD',
                'symbols' => 'ALU' // Código do Alumínio
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $aluminumRate = $data['rates']['ALU'] ?? null;
                
                if ($aluminumRate) {
                    // Converter para tonelada (normalmente vem em outras unidades)
                    $ratePerTon = $aluminumRate * 1000; // Ajustar conforme necessário
                    
                    $this->storeCurrencyRate(CurrencyRate::ALUMINUM, $ratePerTon);
                    Log::info('Cotação Alumínio (MetalsAPI) atualizada', ['rate' => $ratePerTon]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar cotação Alumínio da MetalsAPI', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Gerar valor mock para alumínio (apenas para demonstração)
     */
    private function getMockAluminumRate(): float
    {
        // Valor base fictício em USD por tonelada
        $baseRate = 2000.00;
        
        // Variação aleatória de -5% a +5%
        $variation = (mt_rand(-500, 500) / 100) / 100;
        
        return round($baseRate * (1 + $variation), 2);
    }

    /**
     * Armazenar cotação no banco de dados
     */
    private function storeCurrencyRate(string $type, float $rate): void
    {
        $today = Carbon::today();
        
        CurrencyRate::updateOrCreate(
            [
                'currency_type' => $type,
                'rate_date' => $today
            ],
            [
                'rate' => $rate
            ]
        );
    }

    /**
     * Buscar e armazenar todas as cotações
     */
    public function fetchAllRates(): array
    {
        $results = [];
        
        $results['usd'] = $this->fetchAndStoreUSDRate();
        $results['aluminum'] = $this->fetchAndStoreAluminumRate();
        
        return $results;
    }
}
