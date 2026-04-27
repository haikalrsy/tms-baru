<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class PickList extends Model
{
    protected $fillable = [
        'pl_number', 'so_id', 'assigned_to', 'status',
        'started_at', 'completed_at',
    ];
 
    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];
 
    public function salesOrder()  { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function assignedTo()  { return $this->belongsTo(User::class, 'assigned_to'); }
    public function items()       { return $this->hasMany(PickListItem::class); }
    public function packing()     { return $this->hasOne(Packing::class); }
 
    public function isPending():    bool { return $this->status === 'pending'; }
    public function isInProgress(): bool { return $this->status === 'in_progress'; }
    public function isCompleted():  bool { return $this->status === 'completed'; }
 
    public function getCompletionRateAttribute(): float
    {
        $total  = $this->items->count();
        if (!$total) return 0;
        $picked = $this->items->where('status', 'picked')->count();
        return round(($picked / $total) * 100, 1);
    }
}