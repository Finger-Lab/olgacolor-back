<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CurrencyRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_type',
        'rate',
        'rate_date'
    ];

    protected $casts = [
        'rate_date' => 'date',
        'rate' => 'decimal:4'
    ];

    // Constantes para tipos de moeda
    const USD = 'USD';
    const ALUMINUM = 'ALUMINUM';

    // Scope para filtrar por tipo de moeda
    public function scopeOfType($query, $type)
    {
        return $query->where('currency_type', $type);
    }

    // Scope para obter cotação atual (mais recente)
    public function scopeCurrent($query)
    {
        return $query->orderBy('rate_date', 'desc')->first();
    }

    // Scope para variação diária
    public function scopeDailyVariation($query, $type, $date = null)
    {
        $date = $date ?: Carbon::today();
        
        return $query->where('currency_type', $type)
                    ->where('rate_date', '<=', $date)
                    ->orderBy('rate_date', 'desc')
                    ->limit(2);
    }

    // Scope para variação semanal
    public function scopeWeeklyVariation($query, $type, $date = null)
    {
        $date = $date ?: Carbon::today();
        $weekAgo = Carbon::parse($date)->subWeek();
        
        // Buscar a cotação mais recente até a data atual
        $currentRate = $query->getModel()->newQuery()
                            ->where('currency_type', $type)
                            ->where('rate_date', '<=', $date)
                            ->orderBy('rate_date', 'desc')
                            ->first();
        
        // Buscar a cotação mais próxima de uma semana atrás
        $weekRate = $query->getModel()->newQuery()
                          ->where('currency_type', $type)
                          ->where('rate_date', '<=', $weekAgo)
                          ->orderBy('rate_date', 'desc')
                          ->first();
        
        $collection = collect();
        if ($currentRate) $collection->push($currentRate);
        if ($weekRate) $collection->push($weekRate);
        
        return $collection;
    }

    // Scope para variação mensal
    public function scopeMonthlyVariation($query, $type, $date = null)
    {
        $date = $date ?: Carbon::today();
        $monthAgo = Carbon::parse($date)->subMonth();
        
        // Buscar a cotação mais recente até a data atual
        $currentRate = $query->getModel()->newQuery()
                            ->where('currency_type', $type)
                            ->where('rate_date', '<=', $date)
                            ->orderBy('rate_date', 'desc')
                            ->first();
        
        // Buscar a cotação mais próxima de um mês atrás
        $monthRate = $query->getModel()->newQuery()
                           ->where('currency_type', $type)
                           ->where('rate_date', '<=', $monthAgo)
                           ->orderBy('rate_date', 'desc')
                           ->first();
        
        $collection = collect();
        if ($currentRate) $collection->push($currentRate);
        if ($monthRate) $collection->push($monthRate);
        
        return $collection;
    }

    // Método para calcular variação percentual
    public static function calculateVariation($currentRate, $previousRate)
    {
        if ($previousRate == 0) {
            return 0;
        }
        
        return (($currentRate - $previousRate) / $previousRate) * 100;
    }
}
