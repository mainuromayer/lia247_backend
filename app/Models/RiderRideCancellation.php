<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderRideCancellation extends Model
{
    protected $fillable = [
        'rider_id',
        'ride_id',
    ];

    public function rider()
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }
}
