<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $fillable = ['so_number','erp_id','customer_id','warehouse_id','status','delivery_date','notes','last_synced_at'];
    protected $casts = ['delivery_date' => 'date', 'last_synced_at' => 'datetime'];

    public function customer()    { return $this->belongsTo(Customer::class); }
    public function warehouse()   { return $this->belongsTo(Warehouse::class); }
    public function items()       { return $this->hasMany(SalesOrderItem::class, 'so_id'); }
    public function pickLists()   { return $this->hasMany(PickList::class, 'so_id'); }
    public function deliveryOrders() { return $this->hasMany(DeliveryOrder::class, 'so_id'); }
}
