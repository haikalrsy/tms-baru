<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Packing extends Model
{
    protected $fillable = [
        'packing_number', 'so_id', 'pick_list_id', 'packed_by',
        'status', 'total_boxes', 'total_weight_kg', 'completed_at',
    ];
 
    protected $casts = [
        'completed_at'    => 'datetime',
        'total_weight_kg' => 'decimal:2',
    ];
 
    public function salesOrder()  { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function pickList()    { return $this->belongsTo(PickList::class); }
    public function packedBy()    { return $this->belongsTo(User::class, 'packed_by'); }
    public function items()       { return $this->hasMany(PackingItem::class); }
}
 
 

 