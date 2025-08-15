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
        
        // Buscar a cotação mais recente até a data atual e a de uma semana atrás
        $rates = $query->where('currency_type', $type)
                      ->where(function($q) use ($date, $weekAgo) {
                          $q->where('rate_date', '<=', $date)
                            ->orWhere('rate_date', '<=', $weekAgo);
                      })
                      ->orderBy('rate_date', 'desc')
                      ->limit(10) // Buscar mais registros para garantir que temos dados
                      ->get();
        
        // Filtrar para pegar o mais recente até a data atual e o mais próximo de uma semana atrás
        $currentRate = $rates->where('rate_date', '<=', $date)->first();
        $weekRate = $rates->where('rate_date', '<=', $weekAgo)->first();
        
        $result = collect();
        if ($currentRate) $result->push($currentRate);
        if ($weekRate && $weekRate->id !== $currentRate?->id) $result->push($weekRate);
        
        return $result;
    }

    // Scope para variação mensal
    public function scopeMonthlyVariation($query, $type, $date = null)
    {
        $date = $date ?: Carbon::today();
        $monthAgo = Carbon::parse($date)->subMonth();
        
        // Buscar a cotação mais recente até a data atual e a de um mês atrás
        $rates = $query->where('currency_type', $type)
                      ->where(function($q) use ($date, $monthAgo) {
                          $q->where('rate_date', '<=', $date)
                            ->orWhere('rate_date', '<=', $monthAgo);
                      })
                      ->orderBy('rate_date', 'desc')
                      ->limit(50) // Buscar mais registros para garantir que temos dados
                      ->get();
        
        // Filtrar para pegar o mais recente até a data atual e o mais próximo de um mês atrás
        $currentRate = $rates->where('rate_date', '<=', $date)->first();
        $monthRate = $rates->where('rate_date', '<=', $monthAgo)->first();
        
        $result = collect();
        if ($currentRate) $result->push($currentRate);
        if ($monthRate && $monthRate->id !== $currentRate?->id) $result->push($monthRate);
        
        return $result;
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
