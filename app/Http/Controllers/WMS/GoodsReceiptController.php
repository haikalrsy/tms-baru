<?php 
namespace App\Http\Controllers\WMS;
 
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $list = GoodsReceipt::with(['warehouse:id,name,code', 'items.item:id,sku,name'])
            ->when($request->status,       fn($q,$v) => $q->where('status', $v))
            ->when($request->warehouse_id, fn($q,$v) => $q->where('warehouse_id', $v))
            ->latest()->paginate($request->per_page ?? 20);
        return response()->json(['success' => true, 'data' => $list]);
    }
 
    public function store(Request $request)
    {
        $v = $request->validate([
            'warehouse_id'         => 'required|exists:warehouses,id',
            'so_id'                => 'nullable|exists:sales_orders,id',
            'supplier_name'        => 'nullable|string|max:200',
            'notes'                => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.item_id'      => 'required|exists:items,id',
            'items.*.qty_expected' => 'required|numeric|min:0.01',
            'items.*.rack_id'      => 'nullable|exists:racks,id',
            'items.*.batch_no'     => 'nullable|string|max:100',
            'items.*.expiry_date'  => 'nullable|date',
        ]);
 
        $gr = DB::transaction(function () use ($v, $request) {
            $gr = GoodsReceipt::create([
                'gr_number'    => 'GR-'.date('Ym').'-'.str_pad(GoodsReceipt::count()+1,5,'0',STR_PAD_LEFT),
                'warehouse_id' => $v['warehouse_id'],
                'so_id'        => $v['so_id'] ?? null,
                'supplier_name'=> $v['supplier_name'] ?? null,
                'notes'        => $v['notes'] ?? null,
                'received_by'  => $request->user()->id,
                'status'       => 'draft',
            ]);
            foreach ($v['items'] as $item) {
                $gr->items()->create([
                    'item_id'      => $item['item_id'],
                    'qty_expected' => $item['qty_expected'],
                    'rack_id'      => $item['rack_id'] ?? null,
                    'batch_no'     => $item['batch_no'] ?? null,
                    'expiry_date'  => $item['expiry_date'] ?? null,
                ]);
            }
            return $gr->load('items.item');
        });
        return response()->json(['success'=>true,'message'=>'GR created.','data'=>$gr], 201);
    }
 
    public function show(GoodsReceipt $goodsReceipt)
    {
        return response()->json(['success'=>true,'data'=>$goodsReceipt->load(['warehouse','items.item','items.rack.zone'])]);
    }
 
    public function receive(Request $request, GoodsReceipt $goodsReceipt)
    {
        $request->validate([
            'items'                => 'required|array',
            'items.*.gr_item_id'   => 'required|exists:goods_receipt_items,id',
            'items.*.qty_received' => 'required|numeric|min:0',
            'items.*.qty_good'     => 'nullable|numeric|min:0',
            'items.*.qty_damaged'  => 'nullable|numeric|min:0',
        ]);
        if (!in_array($goodsReceipt->status, ['draft','confirmed'])) {
            return response()->json(['success'=>false,'message'=>'Cannot receive in current status.'],422);
        }
        DB::transaction(function () use ($request, $goodsReceipt) {
            foreach ($request->items as $d) {
                $item = GoodsReceiptItem::find($d['gr_item_id']);
                if (!$item || $item->gr_id !== $goodsReceipt->id) continue;
                $item->update([
                    'qty_received' => $d['qty_received'],
                    'qty_good'     => $d['qty_good']    ?? $d['qty_received'],
                    'qty_damaged'  => $d['qty_damaged'] ?? 0,
                ]);
            }
            $goodsReceipt->update(['status'=>'putaway','received_at'=>now()]);
        });
        return response()->json(['success'=>true,'message'=>'Items received.','data'=>$goodsReceipt->fresh(['items'])]);
    }
 
    public function putaway(Request $request, GoodsReceipt $goodsReceipt)
    {
        $request->validate([
            'items'              => 'required|array',
            'items.*.gr_item_id' => 'required|exists:goods_receipt_items,id',
            'items.*.rack_id'    => 'required|exists:racks,id',
            'items.*.qty'        => 'required|numeric|min:0.01',
        ]);
        if ($goodsReceipt->status !== 'putaway') {
            return response()->json(['success'=>false,'message'=>'GR must be in putaway status.'],422);
        }
        DB::transaction(function () use ($request, $goodsReceipt) {
            foreach ($request->items as $d) {
                $grItem = GoodsReceiptItem::find($d['gr_item_id']);
                if (!$grItem) continue;
                $stock = Stock::firstOrCreate(
                    ['item_id'=>$grItem->item_id,'rack_id'=>$d['rack_id'],'batch_no'=>$grItem->batch_no],
                    ['qty'=>0,'reserved_qty'=>0,'expiry_date'=>$grItem->expiry_date]
                );
                $before = $stock->qty;
                $stock->increment('qty', $d['qty']);
                StockMovement::create([
                    'item_id'=>$grItem->item_id,'rack_id'=>$d['rack_id'],
                    'type'=>'in','qty'=>$d['qty'],
                    'qty_before'=>$before,'qty_after'=>$before+$d['qty'],
                    'ref_type'=>'GoodsReceipt','ref_id'=>$goodsReceipt->id,
                    'created_by'=>request()->user()->id,'moved_at'=>now(),
                ]);
                $grItem->update(['rack_id'=>$d['rack_id']]);
            }
            $goodsReceipt->update(['status'=>'completed']);
        });
        return response()->json(['success'=>true,'message'=>'Putaway completed. Stock updated.']);
    }
}