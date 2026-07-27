<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DrivingInformation extends Model
{
    use HasFactory;

    protected $table = 'driving_informations';
    protected $fillable = [
        'user_id',
        'driving_experience',
        'driving_license',
        'police_reports',
    ];

    protected $casts = [
        'police_reports' => 'array',
        'user_id' => 'integer', // rider, delivery man
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}