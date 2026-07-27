<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = [
        'user_id',
        'cover_photo',
        'business_name',
        'business_type',
        'business_description',
        'business_phone',
        'business_address',
        'opening_time',
        'closing_time',
        'license_number',
        'latitude',
        'longitude',
        'free_delivery',
        'free_delivery_type',
        'admin_contribution',
        'vendor_contribution',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'free_delivery' => 'boolean',
        'admin_contribution' => 'decimal:2',
        'vendor_contribution' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCoverPhotoAttribute($value): string|null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (request()->is('api/*') && !empty($value)) {
            return url($value);
        }
        return $value;
    }
}
