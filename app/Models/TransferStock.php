<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransferStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'sales_order_id',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'driver_id',
        'assigned_by',
        'status',
        'scheduled_at',
        'started_at',
        'picked_at',
        'packed_at',
        'on_the_way_at',
        'put_away_at',
        'delivered_at',
        'cancelled_at',
        'notes',
        'driver_confirmed_at',
        'driver_rejected_at',
        'rejection_reason',
        'put_away_approved_by',
        'put_away_approved_at',
    ];

    protected $casts = [
        'scheduled_at'         => 'datetime',
        'started_at'           => 'datetime',
        'picked_at'            => 'datetime',
        'packed_at'            => 'datetime',
        'on_the_way_at'        => 'datetime',
        'put_away_at'          => 'datetime',
        'delivered_at'         => 'datetime',
        'cancelled_at'         => 'datetime',
        'driver_confirmed_at'  => 'datetime',
        'driver_rejected_at'   => 'datetime',
        'put_away_approved_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function originWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function putAwayApprovedBy()
    {
        return $this->belongsTo(User::class, 'put_away_approved_by');
    }

    public function items()
    {
        return $this->hasMany(TransferStockItem::class);
    }

    public function trackings()
    {
        return $this->hasMany(TransferStockTracking::class)->orderBy('tracked_at');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    public static function allowedDriverTransitions(): array
    {
        return [
            'picking'    => 'packing',
            'packing'    => 'on_the_way',
            'on_the_way' => 'put_away',
        ];
    }
}