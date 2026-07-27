<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'cooking_time',
        'description',
        'image',
        'price',
        'status',
    ];

    protected $casts = [
        'vendor_id' => 'integer',
        'category_id' => 'integer',
        'price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->slug = self::generateUniqueSlug($item->name);
        });

        static::updating(function ($item) {
            if ($item->isDirty('name')) {
                $item->slug = self::generateUniqueSlug($item->name, $item->id);
            }
        });
    }

    private static function generateUniqueSlug($name, $ignoreId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (self::where('slug', $slug)->when($ignoreId, function($q) use ($ignoreId) {
            return $q->where('id', '!=', $ignoreId);
        })->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        return $slug;
    }

    public function vendor()
    {
        return $this->belongsTo(User::class , 'vendor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ItemVariant::class);
    }

    public function toppings()
    {
        return $this->hasMany(ItemTopping::class);
    }



    // Accessor for image URL in API
    public function getImageAttribute($value)
    {
        return $value ? url($value) : null;
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'target_id')->where('role', 'item');
    }

    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }
}