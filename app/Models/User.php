<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
    'name', 'email', 'password', 'role', 'account_status',
    'approved_by', 'approved_at', 'fcm_token', 'is_online',
    'last_login_at', 'last_login_ip',
    // Tambah ini:
    'phone', 'google_id', 'avatar', 'email_verified_at',
];

protected $casts = [
    'approved_at'       => 'datetime',
    'last_login_at'     => 'datetime',
    'last_login_ip'     => 'string', 
    'is_online'         => 'boolean',
    'email_verified_at' => 'datetime',
];

    public function isEmailVerified(): bool { return !is_null($this->email_verified_at); }
    public function isGoogleAccount(): bool { return !is_null($this->google_id); }
    public function driver()       { return $this->hasOne(Driver::class); }
    public function approvedBy()   { return $this->belongsTo(User::class, 'approved_by'); }
    public function deliveryOrders(){ return $this->hasMany(DeliveryOrder::class, 'driver_id'); }
    public function activityLogs() { return $this->hasMany(ActivityLog::class); }

    public function isAdmin():  bool { return $this->role === 'admin'; }
    public function isDriver(): bool { return $this->role === 'driver'; }
    public function isApproved(): bool { return $this->account_status === 'approved'; }
}
