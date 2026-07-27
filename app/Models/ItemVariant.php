<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'name',
        'slug',
        'price',
        'is_featured',
    ];


    protected $casts = [
        'item_id' => 'integer',
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($variant) {
            $variant->slug = \Illuminate\Support\Str::slug($variant->name) . '-' . uniqid();
        });
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
