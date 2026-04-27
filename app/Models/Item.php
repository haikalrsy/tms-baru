<?php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Item extends Model
{
    protected $fillable = [
        'erp_id', 'sku', 'name', 'uom', 'category',
        'weight_kg', 'volume_m3', 'min_stock_alert',
        'is_active', 'last_synced_at',
    ];
 
    protected $casts = [
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
        'weight_kg'      => 'decimal:3',
        'volume_m3'      => 'decimal:4',
    ];
 
    public function stocks()            { return $this->hasMany(Stock::class); }
    public function stockMovements()    { return $this->hasMany(StockMovement::class); }
    public function salesOrderItems()   { return $this->hasMany(SalesOrderItem::class); }
    public function goodsReceiptItems() { return $this->hasMany(GoodsReceiptItem::class); }
    public function pickListItems()     { return $this->hasMany(PickListItem::class); }
    public function packingItems()      { return $this->hasMany(PackingItem::class); }
 
    // Total stok di semua lokasi
    public function getTotalStockAttribute(): float
    {
        return (float) $this->stocks()->sum('qty');
    }
 
    public function getAvailableStockAttribute(): float
    {
        return (float) $this->stocks()->selectRaw('SUM(qty - reserved_qty)')->value('SUM(qty - reserved_qty)') ?? 0;
    }
}
