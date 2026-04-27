<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = ['warehouse_id', 'name', 'code', 'type'];
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function racks()     { return $this->hasMany(Rack::class); }
}
