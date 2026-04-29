<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'item_id', 'warehouse_id', 'rack_id',
        'name', 'sku', 'uom',
        'qty', 'reserved_qty', 'reorder_level',
        'batch_no', 'expiry_date',
    ];

    protected $casts = [
        'expiry_date'   => 'date',
        'qty'           => 'decimal:2',
        'reserved_qty'  => 'decimal:2',
        'reorder_level' => 'decimal:2',
    ];

    public function item()      { return $this->belongsTo(Item::class); }
    public function rack()      { return $this->belongsTo(Rack::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }

    public function getAvailableQtyAttribute(): float
    {
        return max(0, (float)$this->qty - (float)$this->reserved_qty);
    }

    public function hasSufficientStock(float $qty): bool
    {
        return $this->available_qty >= $qty;
    }
}