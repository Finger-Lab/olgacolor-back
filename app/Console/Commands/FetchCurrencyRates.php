<?php

namespace App\Console\Commands;

use App\Services\CurrencyRateService;
use Illuminate\Console\Command;

class FetchCurrencyRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:fetch {--type=all : Tipo de cotação (usd, aluminum, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca e armazena as cotações do dólar e alumínio';

    /**
     * Execute the console command.
     */
    public function handle(CurrencyRateService $currencyService)
    {
        $type = $this->option('type');
        
        $this->info('Iniciando busca de cotações...');
        
        $results = [];
        
        switch ($type) {
            case 'usd':
                $this->info('Buscando cotação do USD...');
                $results['usd'] = $currencyService->fetchAndStoreUSDRate();
                break;
                
            case 'aluminum':
                $this->info('Buscando cotação do Alumínio...');
                $results['aluminum'] = $currencyService->fetchAndStoreAluminumRate();
                break;
                
            case 'all':
            default:
                $this->info('Buscando todas as cotações...');
                $results = $currencyService->fetchAllRates();
                break;
        }
        
        // Exibir resultados
        $this->displayResults($results);
        
        $this->info('Processo finalizado!');
    }
    
    /**
     * Exibir resultados do processo
     */
    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->line('<comment>Resultados:</comment>');
        
        foreach ($results as $currency => $success) {
            $status = $success ? '<info>✓</info>' : '<error>✗</error>';
            $currencyName = $currency === 'usd' ? 'USD' : 'Alumínio';
            $this->line("{$status} {$currencyName}");
        }
        
        $successCount = count(array_filter($results));
        $totalCount = count($results);
        
        $this->newLine();
        $this->line("<comment>Total: {$successCount}/{$totalCount} cotações atualizadas com sucesso</comment>");
    }
}
