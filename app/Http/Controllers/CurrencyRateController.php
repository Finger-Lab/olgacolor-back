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
     * Obter variações (diária, semanal, mensal)
     */
    public function variations(Request $request): JsonResponse
    {
        $type = $request->get('type', CurrencyRate::USD);
        $date = $request->get('date', Carbon::today()->toDateString());

        // Validar tipo
        if (!in_array($type, [CurrencyRate::USD, CurrencyRate::ALUMINUM])) {
            return response()->json(['error' => 'Tipo de moeda inválido'], 400);
        }

        $variations = [];

        // Variação diária
        $dailyRates = CurrencyRate::dailyVariation($type, $date)->get();
        if ($dailyRates->count() >= 2) {
            $current = $dailyRates->first();
            $previous = $dailyRates->last();
            $variations['daily'] = [
                'current' => $current->rate,
                'previous' => $previous->rate,
                'variation' => CurrencyRate::calculateVariation($current->rate, $previous->rate),
                'current_date' => $current->rate_date,
                'previous_date' => $previous->rate_date
            ];
        }

        // Variação semanal
        $weeklyRates = CurrencyRate::weeklyVariation($type, $date);
        if ($weeklyRates->count() >= 2) {
            $current = $weeklyRates->first();
            $previous = $weeklyRates->last();
            $variations['weekly'] = [
                'current' => $current->rate,
                'previous' => $previous->rate,
                'variation' => CurrencyRate::calculateVariation($current->rate, $previous->rate),
                'current_date' => $current->rate_date,
                'previous_date' => $previous->rate_date
            ];
        }

        // Variação mensal
        $monthlyRates = CurrencyRate::monthlyVariation($type, $date);
        if ($monthlyRates->count() >= 2) {
            $current = $monthlyRates->first();
            $previous = $monthlyRates->last();
            $variations['monthly'] = [
                'current' => $current->rate,
                'previous' => $previous->rate,
                'variation' => CurrencyRate::calculateVariation($current->rate, $previous->rate),
                'current_date' => $current->rate_date,
                'previous_date' => $previous->rate_date
            ];
        }

        return response()->json([
            'type' => $type,
            'date' => $date,
            'variations' => $variations
        ]);
    }
}
