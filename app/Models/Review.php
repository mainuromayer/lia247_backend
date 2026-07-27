<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';
    protected $fillable = [
        'order_id',
        'ride_id',
        'user_id',
        'target_id',
        'role',
        'rating',
        'comment'
    ];

    protected $casts = [
        'order_id' => 'integer',
        'ride_id' => 'integer',
        'user_id' => 'integer',
        'target_id' => 'integer',
        'rating' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship based on role.
     */
    public function target()
    {
        if ($this->role === 'item') {
            return $this->belongsTo(Item::class, 'target_id');
        }
        return $this->belongsTo(User::class, 'target_id');
    }
}
