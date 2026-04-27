<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'plate_number', 'vehicle_type', 'brand', 'model', 'year',
        'max_weight_kg', 'max_volume_m3', 'status',
        'stnk_expired_at', 'kir_expired_at',
    ];
    protected $casts = ['stnk_expired_at' => 'date', 'kir_expired_at' => 'date'];

    public function deliveryOrders() { return $this->hasMany(DeliveryOrder::class); }
    public function isAvailable(): bool { return $this->status === 'available'; }
}
