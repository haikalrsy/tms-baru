<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class DeliveryNote extends Model
{
    use SoftDeletes;
 
    protected $fillable = [
        'dn_number', 'delivery_order_id', 'customer_id', 'created_by',
        'delivery_date', 'shipper_name', 'shipper_address',
        'receiver_name', 'receiver_address', 'receiver_phone',
        'vehicle_plate', 'vehicle_type', 'driver_name', 'driver_phone',
        'total_packages', 'total_weight_kg', 'total_volume_m3',
        'cargo_description', 'status', 'pdf_path', 'notes', 'issued_at',
    ];
 
    protected $casts = [
        'delivery_date' => 'date',
        'issued_at'     => 'datetime',
        'total_weight_kg' => 'decimal:2',
        'total_volume_m3' => 'decimal:3',
    ];
 
    public function deliveryOrder() { return $this->belongsTo(DeliveryOrder::class); }
    public function customer()      { return $this->belongsTo(Customer::class); }
    public function createdBy()     { return $this->belongsTo(User::class, 'created_by'); }
    public function items()         { return $this->hasMany(DeliveryNoteItem::class); }
 
    public function isDraft():     bool { return $this->status === 'draft'; }
    public function isIssued():    bool { return $this->status === 'issued'; }
 
    // Auto-generate DN number
    public static function generateNumber(): string
    {
        $prefix = 'DN-' . date('Ym') . '-';
        $last   = static::withTrashed()->where('dn_number', 'like', $prefix . '%')->max('dn_number');
        $next   = $last ? (int) substr($last, -5) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}