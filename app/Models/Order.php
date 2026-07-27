<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_no',
        'user_id',
        'vendor_id',
        'delivery_man_id',
        'stripe_session_id',
        'payment_intent_id',
        'status',
        'cancel_reason',
        'notes',
        'order_type',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'latitude',
        'longitude',
        'delivery_man_address',
        'delivery_man_lat',
        'delivery_man_lng',
        'delivery_option_id',
        'delivery_fee',
        'subtotal',
        'vat_amount',
        'voucher_id',
        'discount_amount',
        'discount_type',
        'total_amount',
        'commission_amount',
        'payment_gateway',
        'checkout_url',
        'confirmation_code',
        'confirmation_code_expires_at',
        'free_delivery',
        'free_delivery_covered_by',
        'admin_delivery_contribution',
        'vendor_delivery_contribution',
        'payment_type',
        'payment_status',
        'amount',
        'transaction_id',
        'refund_transaction_id',
        'refunded_at',
        'refunded_amount',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'vendor_id' => 'integer',
        'delivery_man_id' => 'integer',
        'delivery_option_id' => 'integer',
        'voucher_id' => 'integer',
        'delivery_fee' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'free_delivery' => 'boolean',
        'admin_delivery_contribution' => 'decimal:2',
        'vendor_delivery_contribution' => 'decimal:2',
        'confirmation_code_expires_at' => 'datetime',
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function deliveryMan()
    {
        return $this->belongsTo(User::class, 'delivery_man_id');
    }

    public function deliveryOption()
    {
        return $this->belongsTo(DeliveryOption::class, 'delivery_option_id');
    }

    public function deliveryManCancellations()
    {
        return $this->hasMany(DeliveryManOrderCancellation::class, 'order_id');
    }

    public function getReceiverAddressAttribute($value)
    {
        if (empty($value) && $this->latitude && $this->longitude) {
            try {
                $geoLocation = new \App\Actions\GeoLocation();
                $resolvedAddress = $geoLocation->getAddress($this->latitude, $this->longitude);
                if ($resolvedAddress) {
                    $this->attributes['receiver_address'] = $resolvedAddress;
                    $this->saveQuietly();
                    return $resolvedAddress;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Reverse geocoding accessor failed: ' . $e->getMessage());
            }
        }
        return $value;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $latestOrder = Order::orderBy('id', 'desc')->first();
            if ($latestOrder && $latestOrder->order_no) {
                $lastNumber = (int) str_replace('#ORD', '', $latestOrder->order_no);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 10001;
            }
            $order->order_no = '#ORD' . $nextNumber;
        });
    }
}
