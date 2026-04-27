<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'user_id', 'license_number', 'license_type', 'license_expiry',
        'phone', 'address', 'availability_status',
    ];

    protected $casts = ['license_expiry' => 'date'];

    public function user()    { return $this->belongsTo(User::class); }
    public function isAvailable(): bool { return $this->availability_status === 'available'; }
}
