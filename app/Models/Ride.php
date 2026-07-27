<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    protected $fillable = [
        'ride_no',
        'user_id',
        'rider_id',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'customer_address',
        'customer_lat',
        'customer_lng',
        'rider_address',
        'rider_lat',
        'rider_lng',
        'distance',
        'estimated_fare',
        'actual_fare',
        'commission_amount',
        'total_amount',
        'status',
        'cancel_reason',
        'payment_status',
        'payment_gateway',
        'stripe_session_id',
        'confirmation_code',
        'confirmation_code_expires_at',
        'start_time',
        'end_time',
        'transport_type_id',
        'arrived_at',
        'ready_at',
        'payment_type',
        'amount',
        'transaction_id',
        'refund_transaction_id',
        'refunded_at',
        'refunded_amount',
    ];

    protected $casts = [
        'pickup_lat' => 'double',
        'pickup_lng' => 'double',
        'dropoff_lat' => 'double',
        'dropoff_lng' => 'double',
        'estimated_fare' => 'decimal:2',
        'actual_fare' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'confirmation_code_expires_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'arrived_at' => 'datetime',
        'ready_at' => 'datetime',
        'transport_type_id' => 'integer',
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rider()
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function transportType()
    {
        return $this->belongsTo(TransportType::class);
    }

    public function riderCancellations()
    {
        return $this->hasMany(RiderRideCancellation::class, 'ride_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ride) {
            $latestRide = Ride::orderBy('id', 'desc')->first();
            if ($latestRide && $latestRide->ride_no) {
                $lastNumber = (int) str_replace('#RD', '', $latestRide->ride_no);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 20001;
            }
            $ride->ride_no = '#RD' . $nextNumber;
        });
    }
}
