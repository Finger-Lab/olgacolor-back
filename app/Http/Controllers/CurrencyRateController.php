<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CurrencyRateController extends Controller
{
    /**
     * Listar todas as cotações com filtros opcionais
     */
    public function index(Request $request): JsonResponse
    {
        $query = CurrencyRate::query();

        // Filtro por tipo de moeda
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filtro por data
        if ($request->has('date')) {
            $query->whereDate('rate_date', $request->date);
        }

        // Filtro por período
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('rate_date', [$request->start_date, $request->end_date]);
        }

        $rates = $query->orderBy('rate_date', 'desc')->paginate(15);

        return response()->json($rates);
    }

    /**
     * Criar nova cotação
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency_type' => ['required', Rule::in([CurrencyRate::USD, CurrencyRate::ALUMINUM])],
            'rate' => 'required|numeric|min:0',
            'rate_date' => 'required|date'
        ]);

        try {
            $rate = CurrencyRate::create($validated);
            return response()->json($rate, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao criar cotação',
                'message' => 'Já existe uma cotação para este tipo na data especificada'
            ], 422);
        }
    }

    /**
     * Mostrar cotação específica
     */
    public function show($id): JsonResponse
    {
        $rate = CurrencyRate::findOrFail($id);
        return response()->json($rate);
    }

    /**
     * Atualizar cotação
     */
    public function update(Request $request, $id): JsonResponse
    {
        $rate = CurrencyRate::findOrFail($id);

        $validated = $request->validate([
            'currency_type' => ['sometimes', Rule::in([CurrencyRate::USD, CurrencyRate::ALUMINUM])],
            'rate' => 'sometimes|numeric|min:0',
            'rate_date' => 'sometimes|date'
        ]);

        try {
            $rate->update($validated);
            return response()->json($rate);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao atualizar cotação',
                'message' => 'Já existe uma cotação para este tipo na data especificada'
            ], 422);
        }
    }

    /**
     * Deletar cotação
     */
    public function destroy($id): JsonResponse
    {
        $rate = CurrencyRate::findOrFail($id);
        $rate->delete();

        return response()->json(['message' => 'Cotação deletada com sucesso']);
    }

    /**
     * Obter cotações atuais (mais recentes)
     */
    public function current(): JsonResponse
    {
        $usdRate = CurrencyRate::ofType(CurrencyRate::USD)->orderBy('rate_date', 'desc')->first();
        $aluminumRate = CurrencyRate::ofType(CurrencyRate::ALUMINUM)->orderBy('rate_date', 'desc')->first();

        return response()->json([
            'usd' => $usdRate,
            'aluminum' => $aluminumRate
        ]);
    }

    /**
     * Obter cotações do mês inteiro para o tipo selecionado
     */
    public function monthly(Request $request): JsonResponse
    {
        $type = $request->get('type', CurrencyRate::ALUMINUM);
        $date = $request->get('date', Carbon::today()->toDateString());

        // Validar tipo
        if (!in_array($type, [CurrencyRate::USD, CurrencyRate::ALUMINUM])) {
            return response()->json(['error' => 'Tipo de moeda inválido'], 400);
        }

        $startOfMonth = Carbon::parse($date)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::parse($date)->endOfMonth()->toDateString();

        $monthlyRates = CurrencyRate::ofType($type)
            ->whereBetween('rate_date', [$startOfMonth, $endOfMonth])
            ->orderBy('rate_date', 'asc')
            ->get();

        return response()->json([
            'type' => $type,
            'month' => Carbon::parse($date)->format('Y-m'),
            'start_date' => $startOfMonth,
            'end_date' => $endOfMonth,
            'rates' => $monthlyRates
        ]);
    }

    /**
     * Obter variações (diária, semanal, mensal)
     */
    public function variations(Request $request): JsonResponse
    {
        $type = $request->get('type', CurrencyRate::ALUMINUM);
        $date = $request->get('date', Carbon::today()->toDateString());

        // Validar tipo
        if (!in_array($type, [CurrencyRate::USD, CurrencyRate::ALUMINUM])) {
            return response()->json(['error' => 'Tipo de moeda inválido'], 400);
        }

        $variations = [];

        // Variação diária - buscar cotação de hoje e de ontem
        $todayRate = CurrencyRate::ofType($type)
            ->whereDate('rate_date', $date)
            ->orderBy('rate_date', 'desc')
            ->first();
        
        $yesterdayRate = CurrencyRate::ofType($type)
            ->whereDate('rate_date', '<', $date)
            ->orderBy('rate_date', 'desc')
            ->first();

        $variations['daily'] = [
            'current' => $todayRate ? $todayRate->rate : null,
            'previous' => $yesterdayRate ? $yesterdayRate->rate : null,
            'variation' => ($todayRate && $yesterdayRate) ? CurrencyRate::calculateVariation($todayRate->rate, $yesterdayRate->rate) : null,
            'current_date' => $todayRate ? $todayRate->rate_date : null,
            'previous_date' => $yesterdayRate ? $yesterdayRate->rate_date : null
        ];

        // Variação semanal - buscar cotação atual e de uma semana atrás
        $currentWeekRate = CurrencyRate::ofType($type)
            ->whereDate('rate_date', '<=', $date)
            ->orderBy('rate_date', 'desc')
            ->first();

        $weekAgoDate = Carbon::parse($date)->subWeek();
        $weekAgoRate = CurrencyRate::ofType($type)
            ->whereDate('rate_date', '<=', $weekAgoDate)
            ->orderBy('rate_date', 'desc')
            ->first();

        $variations['weekly'] = [
            'current' => $currentWeekRate ? $currentWeekRate->rate : null,
            'previous' => $weekAgoRate ? $weekAgoRate->rate : null,
            'variation' => ($currentWeekRate && $weekAgoRate) ? CurrencyRate::calculateVariation($currentWeekRate->rate, $weekAgoRate->rate) : null,
            'current_date' => $currentWeekRate ? $currentWeekRate->rate_date : null,
            'previous_date' => $weekAgoRate ? $weekAgoRate->rate_date : null
        ];

        // Variação mensal - buscar cotação atual e de um mês atrás
        $currentMonthRate = CurrencyRate::ofType($type)
            ->whereDate('rate_date', '<=', $date)
            ->orderBy('rate_date', 'desc')
            ->first();

        $monthAgoDate = Carbon::parse($date)->subMonth();
        $monthAgoRate = CurrencyRate::ofType($type)
            ->whereDate('rate_date', '<=', $monthAgoDate)
            ->orderBy('rate_date', 'desc')
            ->first();

        $variations['monthly'] = [
            'current' => $currentMonthRate ? $currentMonthRate->rate : null,
            'previous' => $monthAgoRate ? $monthAgoRate->rate : null,
            'variation' => ($currentMonthRate && $monthAgoRate) ? CurrencyRate::calculateVariation($currentMonthRate->rate, $monthAgoRate->rate) : null,
            'current_date' => $currentMonthRate ? $currentMonthRate->rate_date : null,
            'previous_date' => $monthAgoRate ? $monthAgoRate->rate_date : null
        ];

        return response()->json([
            'type' => $type,
            'date' => $date,
            'variations' => $variations
        ]);
    }
}
