<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProofOfDelivery extends Model
{
    protected $table = 'proof_of_delivery';
    protected $fillable = ['delivery_order_id','submitted_by','recipient_name','recipient_title','photo_path','signature_path','notes','status','verified_by','verified_at','submitted_at'];
    protected $casts = ['verified_at' => 'datetime', 'submitted_at' => 'datetime'];

    public function deliveryOrder() { return $this->belongsTo(DeliveryOrder::class); }
    public function submittedBy()   { return $this->belongsTo(User::class, 'submitted_by'); }
    public function verifiedBy()    { return $this->belongsTo(User::class, 'verified_by'); }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::temporaryUrl($this->photo_path, now()->addMinutes(30)) : null;
    }
    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature_path ? Storage::temporaryUrl($this->signature_path, now()->addMinutes(30)) : null;
    }
}
