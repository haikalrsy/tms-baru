<?php 
namespace App\Http\Controllers\WMS;
 
use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Models\Rack;
use App\Models\Stock;
use Illuminate\Http\Request;
 
class WarehouseController extends Controller
{
    // GET /api/wms/warehouses
    public function index(Request $request)
    {
        $warehouses = Warehouse::with(['zones.racks'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, fn($q, $v) =>
                $q->where('name','like',"%$v%")->orWhere('code','like',"%$v%")
            )
            ->orderBy('name')
            ->get()
            ->map(function ($wh) {
                // Hitung utilization dari stok
                $totalCapacity = $wh->zones->flatMap->racks->sum('max_weight_kg') ?: 1;
                $usedWeight    = Stock::whereHas('rack.zone', fn($q) => $q->where('warehouse_id', $wh->id))
                    ->selectRaw('SUM(qty) as total')->value('total') ?? 0;
                $wh->capacity    = $totalCapacity;
                $wh->used        = $usedWeight;
                $wh->utilization = round(($usedWeight / $totalCapacity) * 100, 1);
                $wh->total_zones = $wh->zones->count();
                $wh->total_racks = $wh->zones->flatMap->racks->count();
                return $wh;
            });
 
        return response()->json(['success' => true, 'data' => $warehouses]);
    }
 
    // POST /api/wms/warehouses
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'code'      => 'required|string|max:20|unique:warehouses,code',
            'address'   => 'nullable|string',
            'city'      => 'nullable|string|max:100',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone'     => 'nullable|string|max:20',
            'pic_name'  => 'nullable|string|max:100',
            'status'    => 'in:active,inactive',
        ]);
        $wh = Warehouse::create($validated);
        return response()->json(['success' => true, 'message' => 'Warehouse created.', 'data' => $wh], 201);
    }
 
    // GET /api/wms/warehouses/{id}
    public function show(Warehouse $warehouse)
    {
        $warehouse->load(['zones' => fn($q) => $q->with(['racks' => fn($rq) => $rq->withCount('stocks')])]);
        return response()->json(['success' => true, 'data' => $warehouse]);
    }
 
    // PUT /api/wms/warehouses/{id}
    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'code'      => 'sometimes|string|max:20|unique:warehouses,code,' . $warehouse->id,
            'address'   => 'nullable|string',
            'city'      => 'nullable|string|max:100',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'phone'     => 'nullable|string|max:20',
            'pic_name'  => 'nullable|string|max:100',
            'status'    => 'in:active,inactive',
        ]);
        $warehouse->update($validated);
        return response()->json(['success' => true, 'data' => $warehouse]);
    }
 
    // DELETE /api/wms/warehouses/{id}
    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return response()->json(['success' => true, 'message' => 'Warehouse deleted.']);
    }
 
    // GET /api/wms/warehouses/{id}/zones
    public function zones(Warehouse $warehouse)
    {
        return response()->json(['success' => true, 'data' => $warehouse->zones()->with('racks')->get()]);
    }
 
    // GET /api/wms/warehouses/{id}/stocks
    public function stocks(Request $request, Warehouse $warehouse)
    {
        $stocks = Stock::with(['item:id,sku,name,uom', 'rack.zone'])
            ->whereHas('rack.zone', fn($q) => $q->where('warehouse_id', $warehouse->id))
            ->where('qty', '>', 0)
            ->paginate($request->per_page ?? 50);
        return response()->json(['success' => true, 'data' => $stocks]);
    }
}