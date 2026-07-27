<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportType extends Model
{
    protected $fillable = [
        'transport_type_category_id',
        'name',
        'transport_type',
        'icon',
        'base_fare',
        'price_per_km',
        'price_per_minute',
        'min_balance',
        'is_active',
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'price_per_minute' => 'decimal:2',
        'min_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(TransportTypeCategory::class, 'transport_type_category_id');
    }

    public function rides()
    {
        return $this->hasMany(Ride::class);
    }
}
