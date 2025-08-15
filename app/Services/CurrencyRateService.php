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
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->get('https://api.exchangerate-api.com/v4/latest/USD');
            
            if ($response->successful()) {
                $data = $response->json();
                $brlRate = $data['rates']['BRL'] ?? null;
                
                if ($brlRate) {
                    // $brlRate já representa quantos reais equivalem a 1 USD
                    $usdRate = $brlRate;
                    
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
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->get("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoMoedaDia(moeda=@moeda,dataCotacao=@dataCotacao)", [
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
     * Buscar e armazenar cotação do Alumínio via scraping
     */
    public function fetchAndStoreAluminumRate(): bool
    {
        try {
            $aluminumRate = $this->scrapeAluminumPrice();
            
            if ($aluminumRate) {
                $this->storeCurrencyRate(CurrencyRate::ALUMINUM, $aluminumRate);
                Log::info('Cotação Alumínio atualizada via scraping', ['rate' => $aluminumRate]);
                return true;
            }
            
            Log::error('Erro ao buscar cotação Alumínio', ['error' => 'Erro desconhecido']);
            return false;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar cotação Alumínio', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Fazer scraping do preço do alumínio no Trading Economics
     */
    private function scrapeAluminumPrice(): ?float
    {
        try {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ])->get('https://tradingeconomics.com/commodity/aluminum');

            if ($response->successful()) {
                $html = $response->body();
                
                // Carregar HTML no DOMDocument
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($html);
                libxml_clear_errors();
                
                // Usar XPath para encontrar o elemento
                $xpath = new \DOMXPath($dom);
                
                // Queries em ordem de prioridade
                $queries = [
                    '//div[@id="item_definition"]//div[@class="table-responsive"]//table//tbody//tr//td[2]', // Query original
                    '//div[@id="item_definition"]//table//tr//td[2]', // Query simplificada que funcionou
                    '//div[@id="item_definition"]//td[2]', // Query ainda mais simples
                ];
                
                foreach ($queries as $xpathQuery) {
                    $nodes = $xpath->query($xpathQuery);
                    
                    if ($nodes->length > 0) {
                        $priceText = trim($nodes->item(0)->textContent);
                        
                        // Verificar se parece com um preço válido (formato: NNNN.NN)
                        if (preg_match('/\d{3,4}\.\d{2}/', $priceText)) {
                            // Remover caracteres não numéricos exceto ponto
                            $cleanPrice = preg_replace('/[^0-9.]/', '', $priceText);
                            $price = floatval($cleanPrice);
                            
                            // Validar se está em um range razoável para preço do alumínio (USD por tonelada)
                            if ($price > 1000 && $price < 10000) {
                                Log::info('Preço do alumínio obtido via scraping', ['price' => $price, 'raw' => $priceText]);
                                return $price;
                            }
                        }
                    }
                }
                
                Log::warning('Elemento não encontrado no scraping do Trading Economics');
                
            } else {
                Log::warning('Resposta não bem-sucedida do Trading Economics', ['status' => $response->status()]);
            }
            
        } catch (\Exception $e) {
            Log::error('Erro no scraping do Trading Economics', ['error' => $e->getMessage()]);
        }
        
        return null;
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

            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->get('https://metals-api.com/api/latest', [
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
