<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_id', 
        'path'
    ];

    public function market() {
        return $this->belongsTo(Market::class);
    }
}
