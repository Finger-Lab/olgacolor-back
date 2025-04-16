<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarketController extends Controller
{
    public function index() {
        $markets = Market::with(['images', 'imagesTypologies'])->get();

        return response()->json($markets);
    }       
    
    public function create(Request $request) {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'air_permeability' => 'required|numeric',
            'water_tightness' => 'required|numeric',
            'wind_resistance' => 'required|numeric',
            'acoustic_insulation' => 'required|numeric',
            'thermal_transmittance' => 'required|numeric',
            'glazing_thickness' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'weight' => 'required|numeric',
            'theoretical_thickness' => 'required|numeric',
            'highlights' => 'required|array',
            'highlights.*' => 'string',
            'logo' => 'image|mimes:jpeg,png,jpg|max:2048',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'imagesTypologies.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);    

        $data = $request->except(['images', 'imagesTypologies']);
        $data['highlights'] = json_encode($data['highlights']);
        $market = Market::create($data);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('public/uploads/imagesMarket');

                $market->images()->create([
                    'path' => Storage::url($path)
                ]);
            };
        }

        if ($request->hasFile('imagesTypologies')) {
            foreach ($request->file('imagesTypologies') as $image) {
                $path = $image->store('public/uploads/imagesTypologies');

                $market->imagesTypologies()->create([
                    'path' => Storage::url($path)
                ]);
            };
        }

        return response()->json($market);
    }

    public function show($id) {
        $market = Market::with('images')->findOrFail(($id));

        return response()->json($market);
    }
}
