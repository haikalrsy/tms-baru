<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrder extends Model
{
    protected $fillable = [
        'so_number',
        'customer_name',
        'customer_code',
        'customer_address',
        'customer_phone',
        'customer_email',
        'order_date',
        'due_date',
        'payment_terms',
        'notes',
        'subtotal',
        'discount',
        'total',
        'status',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'due_date'   => 'date',
        'subtotal'   => 'decimal:2',
        'discount'   => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'so_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transferStocks(): HasMany
    {
        return $this->hasMany(TransferStock::class);
    }
}