<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['erp_id','name','code','phone','email','address','city','is_active','last_synced_at'];
    protected $casts = ['is_active' => 'boolean', 'last_synced_at' => 'datetime'];

    public function salesOrders()    { return $this->hasMany(SalesOrder::class); }
    public function deliveryOrders() { return $this->hasMany(DeliveryOrder::class); }
}
