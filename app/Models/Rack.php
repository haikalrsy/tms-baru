<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Rack extends Model
{
    protected $fillable = ['zone_id', 'code', 'full_code', 'total_levels', 'max_weight_kg', 'status'];
    public function zone()   { return $this->belongsTo(Zone::class); }
    public function stocks() { return $this->hasMany(Stock::class); }
}
