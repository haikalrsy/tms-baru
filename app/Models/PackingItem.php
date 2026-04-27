<?php

// ============================================================
// app/Models/PackingItem.php
// ============================================================
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class PackingItem extends Model
{
    protected $fillable = [
        'packing_id', 'item_id', 'qty', 'box_label',
    ];
 
    protected $casts = [
        'qty' => 'decimal:2',
    ];
 
    public function packing() { return $this->belongsTo(Packing::class); }
    public function item()    { return $this->belongsTo(Item::class); }
}
