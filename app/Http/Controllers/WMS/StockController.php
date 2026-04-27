<?php 
namespace App\Http\Controllers\WMS;
 
use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
class StockController extends Controller
{
    public function index(Request $request)
    {
        $stocks = Stock::with(['item:id,sku,name,uom','rack.zone.warehouse'])
            ->when($request->warehouse_id, fn($q,$v) =>
                $q->whereHas('rack.zone', fn($zq) => $zq->where('warehouse_id', $v))
            )
            ->when($request->item_id, fn($q,$v) => $q->where('item_id', $v))
            ->when($request->search, fn($q,$v) =>
                $q->whereHas('item', fn($iq) => $iq->where('name','like',"%$v%")->orWhere('sku','like',"%$v%"))
            )
            ->where('qty', '>', 0)
            ->paginate($request->per_page ?? 50);
        return response()->json(['success'=>true,'data'=>$stocks]);
    }
 
    public function summary(Request $request)
    {
        $summary = Stock::selectRaw('item_id, SUM(qty) as total_qty, SUM(reserved_qty) as total_reserved, COUNT(DISTINCT rack_id) as locations')
            ->with('item:id,sku,name,uom,min_stock_alert')
            ->groupBy('item_id')
            ->when($request->warehouse_id, fn($q,$v) =>
                $q->whereHas('rack.zone', fn($zq) => $zq->where('warehouse_id', $v))
            )
            ->get();
        return response()->json(['success'=>true,'data'=>$summary]);
    }
 
    public function adjust(Request $request)
    {
        $request->validate([
            'item_id'  => 'required|exists:items,id',
            'rack_id'  => 'required|exists:racks,id',
            'qty'      => 'required|numeric|not_in:0',
            'type'     => 'required|in:adjustment,opname',
            'notes'    => 'nullable|string|max:255',
            'batch_no' => 'nullable|string|max:100',
        ]);
        DB::transaction(function () use ($request) {
            $stock = Stock::firstOrCreate(
                ['item_id'=>$request->item_id,'rack_id'=>$request->rack_id,'batch_no'=>$request->batch_no],
                ['qty'=>0,'reserved_qty'=>0]
            );
            $before = $stock->qty;
            $newQty = max(0, $before + $request->qty);
            $stock->update(['qty'=>$newQty]);
            StockMovement::create([
                'item_id'=>$request->item_id,'rack_id'=>$request->rack_id,
                'type'=>$request->type,'qty'=>$request->qty,
                'qty_before'=>$before,'qty_after'=>$newQty,
                'notes'=>$request->notes,'created_by'=>$request->user()->id,'moved_at'=>now(),
            ]);
        });
        return response()->json(['success'=>true,'message'=>'Stock adjusted.']);
    }
 
    public function movements(Request $request)
    {
        $movements = StockMovement::with(['item:id,sku,name','rack.zone.warehouse','createdBy:id,name'])
            ->when($request->item_id,   fn($q,$v) => $q->where('item_id', $v))
            ->when($request->type,      fn($q,$v) => $q->where('type', $v))
            ->when($request->date_from, fn($q,$v) => $q->whereDate('moved_at','>=',$v))
            ->when($request->date_to,   fn($q,$v) => $q->whereDate('moved_at','<=',$v))
            ->latest('moved_at')
            ->paginate($request->per_page ?? 30);
        return response()->json(['success'=>true,'data'=>$movements]);
    }
 
    public function lowStock()
    {
        $low = Stock::selectRaw('item_id, SUM(qty) as total_qty')
            ->with('item:id,sku,name,uom,min_stock_alert')
            ->groupBy('item_id')
            ->havingRaw('SUM(qty) <= (SELECT min_stock_alert FROM items WHERE id = item_id AND min_stock_alert > 0)')
            ->get();
        return response()->json(['success'=>true,'data'=>$low]);
    }
}