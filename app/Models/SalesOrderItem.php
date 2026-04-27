<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class SalesOrderItem extends Model
{
    protected $fillable = [
        'so_id', 'item_id', 'qty_ordered', 'qty_delivered',
    ];
 
    protected $casts = [
        'qty_ordered'   => 'decimal:2',
        'qty_delivered' => 'decimal:2',
    ];
 
    public function salesOrder() { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function item()       { return $this->belongsTo(Item::class); }
 
    public function getRemainingQtyAttribute(): float
    {
        return max(0, $this->qty_ordered - $this->qty_delivered);
    }
}
