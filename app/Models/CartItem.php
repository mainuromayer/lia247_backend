<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'item_id',
        'variant_id',
        'selected_toppings',
        'quantity',
        'unit_price',
        'special_instruction'
    ];

    protected $casts = [
        'selected_toppings' => 'json',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'cart_id' => 'integer',
        'item_id' => 'integer',
        'variant_id' => 'integer',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
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