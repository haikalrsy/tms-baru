<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class GoodsReceiptItem extends Model
{
    protected $fillable = [
        'gr_id', 'item_id', 'qty_expected', 'qty_received',
        'qty_good', 'qty_damaged', 'rack_id', 'batch_no', 'expiry_date',
    ];
 
    protected $casts = [
        'expiry_date'  => 'date',
        'qty_expected' => 'decimal:2',
        'qty_received' => 'decimal:2',
        'qty_good'     => 'decimal:2',
        'qty_damaged'  => 'decimal:2',
    ];
 
    public function goodsReceipt() { return $this->belongsTo(GoodsReceipt::class, 'gr_id'); }
    public function item()         { return $this->belongsTo(Item::class); }
    public function rack()         { return $this->belongsTo(Rack::class); }
}
 