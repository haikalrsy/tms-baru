<?php

namespace App\Http\Controllers\WMS;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    // GET /api/wms/warehouses
    public function index(Request $request)
    {
        $warehouses = Warehouse::with(['zones.racks'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, fn($q, $v) =>
                $q->where('name', 'like', "%$v%")->orWhere('code', 'like', "%$v%")
            )
            ->orderBy('name')
            ->get()
            ->map(function ($wh) {
                $stocks          = Stock::where('warehouse_id', $wh->id)->get();
                $totalQty        = $stocks->sum('qty');
                $wh->utilization = min(100, round($totalQty / max(1, $stocks->count() * 100) * 100, 1));
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
            'address'   => 'nullable|string',
            'city'      => 'nullable|string|max:100',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone'     => 'nullable|string|max:20',
            'pic_name'  => 'nullable|string|max:100',
            'status'    => 'in:active,inactive',
        ]);

        // Auto-generate code dari nama
        $validated['code'] = $this->generateCode($validated['name']);

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
            'address'   => 'nullable|string',
            'city'      => 'nullable|string|max:100',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'phone'     => 'nullable|string|max:20',
            'pic_name'  => 'nullable|string|max:100',
            'status'    => 'in:active,inactive',
        ]);

        // Regenerate code kalau nama berubah dan code belum di-set manual
        if (isset($validated['name']) && $validated['name'] !== $warehouse->name) {
            $validated['code'] = $this->generateCode($validated['name'], $warehouse->id);
        }

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
        $stocks = Stock::where('warehouse_id', $warehouse->id)
            ->when($request->search, fn($q) =>
                $q->where(function ($q2) use ($request) {
                    $q2->where('name', 'like', "%{$request->search}%")
                       ->orWhere('sku', 'like', "%{$request->search}%");
                })
            )
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $stocks]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function generateCode(string $name, ?int $excludeId = null): string
    {
        // Ambil huruf kapital dari tiap kata, maks 6 karakter
        $words  = preg_split('/\s+/', trim($name));
        $prefix = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), $words)));
        $prefix = substr($prefix, 0, 4);

        // Cari nomor urut yang belum dipakai
        $counter = 1;
        do {
            $code  = $prefix . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $query = Warehouse::where('code', $code);
            if ($excludeId) $query->where('id', '!=', $excludeId);
            $exists = $query->exists();
            $counter++;
        } while ($exists);

        return $code;
    }
}