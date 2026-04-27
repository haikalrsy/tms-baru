<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id','action','subject_type','subject_id','old_values','new_values','ip_address','user_agent','created_at'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }

    public static function log(string $action, $subject = null, array $oldValues = [], array $newValues = [], ?int $userId = null): void
    {
        static::create([
            'user_id'      => $userId ?? auth()->id(),
            'action'       => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id'   => $subject?->id,
            'old_values'   => $oldValues ?: null,
            'new_values'   => $newValues ?: null,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'created_at'   => now(),
        ]);
    }
}
