<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index() {
        $markets = Market::all();
        return response()->json($markets, 200);
    }       
    
    public function create(Request $request) {
        $data = $request->all();
        $market = Market::create($data);
        return response()->json($market, 201);
    }
}
