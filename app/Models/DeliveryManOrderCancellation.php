<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryManOrderCancellation extends Model
{
    protected $fillable = [
        'delivery_man_id',
        'order_id',
    ];

    public function deliveryMan()
    {
        return $this->belongsTo(User::class, 'delivery_man_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
