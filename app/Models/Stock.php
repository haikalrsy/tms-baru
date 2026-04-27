<?php
namespace App\Models;
 
use App\Models\Item;
use App\Models\Rack;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
 
class Stock extends Model
{
    protected $fillable = [
        'item_id', 'rack_id', 'qty', 'reserved_qty',
        'batch_no', 'expiry_date',
    ];
 
    protected $casts = [
        'expiry_date'  => 'date',
        'qty'          => 'decimal:2',
        'reserved_qty' => 'decimal:2',
    ];
 
    public function item()      { return $this->belongsTo(Item::class); }
    public function rack()      { return $this->belongsTo(Rack::class); }
    public function movements() { return $this->hasMany(StockMovement::class, 'rack_id', 'rack_id')
    ->where('item_id', $this->item_id); }
 
    // Stok yang bisa digunakan (exclude reserved)
    public function getAvailableQtyAttribute(): float
    {
        return max(0, (float)$this->qty - (float)$this->reserved_qty);
    }
 
    // Cek apakah stok cukup untuk qty tertentu
    public function hasSufficientStock(float $qty): bool
    {
        return $this->available_qty >= $qty;
    }
 
    // Scope: stok yang hampir habis
    public function scopeLowStock($query)
    {
        return $query->whereHas('item', function ($q) {
            $q->whereColumn('min_stock_alert', '>', \DB::raw('0'));
        })->whereRaw('qty <= (SELECT min_stock_alert FROM items WHERE id = stocks.item_id)');
    }
 
    // Scope: stok yang sudah/hampir expired
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
->where('expiry_date', '<=', now()->addDays($days))
->where('qty', '>', 0);
    }
}