<?php 
namespace App\Http\Controllers\WMS;
 
use App\Http\Controllers\Controller;
use App\Models\PickList;
use App\Models\PickListItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
class PickListController extends Controller
{
    public function index(Request $request)
    {
        $list = PickList::with(['salesOrder:id,so_number','assignedTo:id,name'])
            ->when($request->status,      fn($q,$v) => $q->where('status', $v))
            ->when($request->assigned_to, fn($q,$v) => $q->where('assigned_to', $v))
            ->latest()->paginate($request->per_page ?? 20);
        return response()->json(['success'=>true,'data'=>$list]);
    }
 
    public function store(Request $request)
    {
        $request->validate([
            'so_id'                => 'required|exists:sales_orders,id',
            'assigned_to'          => 'nullable|exists:users,id',
            'items'                => 'required|array|min:1',
            'items.*.item_id'      => 'required|exists:items,id',
            'items.*.rack_id'      => 'required|exists:racks,id',
            'items.*.qty_required' => 'required|numeric|min:0.01',
        ]);
        $pl = DB::transaction(function () use ($request) {
            $pl = PickList::create([
                'pl_number'   => 'PL-'.date('Ym').'-'.str_pad(PickList::count()+1,5,'0',STR_PAD_LEFT),
                'so_id'       => $request->so_id,
                'assigned_to' => $request->assigned_to,
                'status'      => 'pending',
            ]);
            foreach ($request->items as $item) {
                Stock::where('item_id',$item['item_id'])->where('rack_id',$item['rack_id'])
                     ->increment('reserved_qty', $item['qty_required']);
                $pl->items()->create([
                    'item_id'      => $item['item_id'],
                    'rack_id'      => $item['rack_id'],
                    'qty_required' => $item['qty_required'],
                    'status'       => 'pending',
                ]);
            }
            return $pl->load('items.item','items.rack');
        });
        return response()->json(['success'=>true,'message'=>'Pick list created.','data'=>$pl], 201);
    }
 
    public function show(PickList $pickList)
    {
        return response()->json(['success'=>true,'data'=>$pickList->load(['items.item','items.rack.zone','salesOrder','assignedTo:id,name'])]);
    }
 
    public function complete(Request $request, PickList $pickList)
    {
        $request->validate([
            'items'              => 'required|array',
            'items.*.pl_item_id' => 'required|exists:pick_list_items,id',
            'items.*.qty_picked' => 'required|numeric|min:0',
        ]);
        DB::transaction(function () use ($request, $pickList) {
            foreach ($request->items as $d) {
                $plItem = PickListItem::find($d['pl_item_id']);
                if (!$plItem || $plItem->pick_list_id !== $pickList->id) continue;
                $plItem->update([
                    'qty_picked' => $d['qty_picked'],
                    'status'     => $d['qty_picked'] >= $plItem->qty_required ? 'picked' : 'short',
                ]);
                $stock = Stock::where('item_id',$plItem->item_id)->where('rack_id',$plItem->rack_id)->first();
                if ($stock) {
                    $before = $stock->qty;
                    $stock->decrement('qty', $d['qty_picked']);
                    $stock->decrement('reserved_qty', $plItem->qty_required);
                    StockMovement::create([
                        'item_id'=>$plItem->item_id,'rack_id'=>$plItem->rack_id,
                        'type'=>'out','qty'=>-$d['qty_picked'],
                        'qty_before'=>$before,'qty_after'=>$before-$d['qty_picked'],
                        'ref_type'=>'PickList','ref_id'=>$pickList->id,
                        'created_by'=>$request->user()->id,'moved_at'=>now(),
                    ]);
                }
            }
            $pickList->update(['status'=>'completed','completed_at'=>now()]);
        });
        return response()->json(['success'=>true,'message'=>'Pick list completed.']);
    }
}
 