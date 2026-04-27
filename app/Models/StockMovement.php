<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class StockMovement extends Model
{
    protected $fillable = [
        'item_id', 'rack_id', 'type', 'qty',
        'qty_before', 'qty_after', 'ref_type', 'ref_id',
        'notes', 'created_by', 'moved_at',
    ];
 
    protected $casts = [
        'moved_at'   => 'datetime',
        'qty'        => 'decimal:2',
        'qty_before' => 'decimal:2',
        'qty_after'  => 'decimal:2',
    ];
 
    // Tipe yang tersedia
    const TYPES = ['in', 'out', 'transfer', 'adjustment', 'opname'];
 
    public function item()      { return $this->belongsTo(Item::class); }
    public function rack()      { return $this->belongsTo(Rack::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
 
    // Polymorphic — bisa ambil referensi (GoodsReceipt, PickList, dll)
    public function reference()
    {
        if (!$this->ref_type || !$this->ref_id) return null;
        $map = [
            'GoodsReceipt' => GoodsReceipt::class,
            'PickList'     => PickList::class,
            'Packing'      => Packing::class,
        ];
        $class = $map[$this->ref_type] ?? null;
        return $class ? $class::find($this->ref_id) : null;
    }
 
    public function isInbound():  bool { return $this->type === 'in'; }
    public function isOutbound(): bool { return $this->type === 'out'; }
}
 
