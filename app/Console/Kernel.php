<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Executar busca de cotações diariamente às 14:00
        $schedule->command('currency:fetch')
                 ->dailyAt('14:00')
                 ->timezone('America/Sao_Paulo')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/currency-fetch.log'));
        
        // Backup: tentar novamente às 17:00 se falhou pela manhã
        $schedule->command('currency:fetch')
                 ->dailyAt('17:00')
                 ->timezone('America/Sao_Paulo')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/currency-fetch.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
