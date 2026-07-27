<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'item_id',
        'variant_id',
        'selected_toppings',
        'item_name',
        'quantity',
        'price',                    // unit price
        'total_price',              
        'special_instruction',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'item_id' => 'integer',
        'variant_id' => 'integer',
        'selected_toppings' => 'json',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function variant()
    {
        return $this->belongsTo(ItemVariant::class);
    }
}