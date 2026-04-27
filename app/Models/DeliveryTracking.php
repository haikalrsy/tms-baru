<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryTracking extends Model
{
    protected $table = 'delivery_tracking';
    protected $fillable = ['delivery_order_id','driver_id','latitude','longitude','speed_kmh','heading','accuracy_m','status_snapshot','tracked_at'];
    protected $casts = ['tracked_at' => 'datetime'];

    public function deliveryOrder() { return $this->belongsTo(DeliveryOrder::class); }
    public function driver()        { return $this->belongsTo(User::class, 'driver_id'); }
}
