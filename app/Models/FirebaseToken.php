<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirebaseToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_id',
        'is_active',
    ];

    // Type casting for attributes
    protected $casts = [
        'user_id'           => 'integer',
        'device_id'         => 'string',
        'token'             => 'string',
        'is_active'         => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
