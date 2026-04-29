<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name', 'code', 'address', 'city',
        'latitude', 'longitude',
        'phone', 'pic_name', 'status',
    ];

    public function zones()
    {
        return $this->hasMany(Zone::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}