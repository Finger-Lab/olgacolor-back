<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'air_permeability',
        'water_tightness',
        'wind_resistance',
        'acoustic_insulation',
        'thermal_transmittance',
        'glazing_thickness',
        'width',
        'height',
        'weight',
        'theoretical_thickness',
        'highlights'
    ];

    public function images() {
        return $this->hasMany(MarketImage::class);
    }

    public function imagesTypologies() {
        return $this->hasMany(ImagesTypologies::class);
    }
}
