<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'do_number','so_id','customer_id','driver_id','vehicle_id','created_by',
        'status','origin_address','origin_lat','origin_lng',
        'destination_address','destination_lat','destination_lng',
        'total_weight_kg','notes','scheduled_at',
        'accepted_at','departed_at','arrived_at','delivered_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime', 'accepted_at'  => 'datetime',
        'departed_at'  => 'datetime', 'arrived_at'   => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function customer()    { return $this->belongsTo(Customer::class); }
    public function driver()      { return $this->belongsTo(User::class, 'driver_id'); }
    public function vehicle()     { return $this->belongsTo(Vehicle::class); }
    public function salesOrder()  { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function tracking()    { return $this->hasMany(DeliveryTracking::class); }
    public function latestTracking() { return $this->hasOne(DeliveryTracking::class)->latestOfMany('tracked_at'); }
    public function pod()         { return $this->hasOne(ProofOfDelivery::class); }
    public function routePlan()   { return $this->hasOne(RoutePlan::class); }
    public function createdBy()   { return $this->belongsTo(User::class, 'created_by'); }

    public function canAssignDriver(): bool { return $this->status === 'waiting_assignment'; }
    public function isActive(): bool { return in_array($this->status, ['assigned','accepted','loading','in_transit','arrived']); }
}
