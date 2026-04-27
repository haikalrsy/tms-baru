<?php  
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class PickListItem extends Model
{
    protected $fillable = [
        'pick_list_id', 'item_id', 'rack_id',
        'qty_required', 'qty_picked', 'status',
    ];
 
    protected $casts = [
        'qty_required' => 'decimal:2',
        'qty_picked'   => 'decimal:2',
    ];
 
    public function pickList() { return $this->belongsTo(PickList::class); }
    public function item()     { return $this->belongsTo(Item::class); }
    public function rack()     { return $this->belongsTo(Rack::class); }
 
    public function isShort(): bool
    {
        return $this->qty_picked < $this->qty_required;
    }
}