<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class VehicleInformation extends Model
{
    use HasFactory;

    protected $table = 'vehicle_informations';
    protected $fillable = [
        'user_id',
        'vehicle_type',
        'vehicle_model',
        'vehicle_color',
        'manufacture_year',
        'insurance_number',
        'license_number',
        'max_occupancy',
        'vehicle_photos',
        'license_front',
        'license_back',
    ];

    protected $casts = [
        'vehicle_photos' => 'array',
        'user_id' => 'integer', // rider, delivery man
        'max_occupancy' => 'integer', // rider, delivery man
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}