<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;
    protected $table = 'chat_messages';
    protected $fillable = [
        'order_id',
        'ride_id',
        'sender_id',
        'receiver_id',
        'message',
        'files',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'files' => 'array',
        'sender_id' => 'integer',
        'receiver_id' => 'integer',
        'order_id' => 'integer',
        'ride_id' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

}
