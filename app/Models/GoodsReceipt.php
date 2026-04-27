<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class GoodsReceipt extends Model
{
    protected $fillable = [
        'gr_number', 'so_id', 'warehouse_id', 'received_by',
        'supplier_name', 'notes', 'status', 'received_at',
    ];
 
    protected $casts = [
        'received_at' => 'datetime',
    ];
 
    public function warehouse()   { return $this->belongsTo(Warehouse::class); }
    public function salesOrder()  { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function receivedBy()  { return $this->belongsTo(User::class, 'received_by'); }
    public function items()       { return $this->hasMany(GoodsReceiptItem::class, 'gr_id'); }
 
    public function canReceive(): bool
    {
        return in_array($this->status, ['draft', 'confirmed']);
    }
 
    public function canPutaway(): bool
    {
        return $this->status === 'putaway';
    }
}
