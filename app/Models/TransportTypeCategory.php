<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportTypeCategory extends Model
{
    protected $fillable = ['name', 'status'];

    public function transportTypes()
    {
        return $this->hasMany(TransportType::class, 'transport_type_category_id');
    }
}
