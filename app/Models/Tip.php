<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tip extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_id',
        'order_id',
        'ride_id',
        'amount',
        'payment_type',
        'payment_status',
        'transaction_id',
        'payment_intent_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'recipient_id' => 'integer',
        'order_id' => 'integer',
        'ride_id' => 'integer',
        'amount' => 'decimal:2',
    ];

    /**
     * Customer who paid the tip
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Rider or Delivery Man who received the tip
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Order associated with the tip (if any)
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Ride associated with the tip (if any)
     */
    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }
}
