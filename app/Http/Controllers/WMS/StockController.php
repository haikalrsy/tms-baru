<?php

namespace App\Http\Controllers\WMS;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    // GET /wms/stocks
    public function index(Request $request)
    {
        $stocks = Stock::with('warehouse:id,name,code')
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', "%{$request->search}%")
                   ->orWhere('sku', 'like', "%{$request->search}%");
            }))
            ->latest()
            ->paginate(50);

        return response()->json($stocks);
    }

    // GET /wms/stocks/summary
    public function summary()
    {
        return response()->json([
            'total_skus'   => Stock::count(),
            'low_stock'    => Stock::whereColumn('qty', '<=', 'reorder_level')->count(),
            'out_of_stock' => Stock::where('qty', 0)->count(),
            'total_qty'    => Stock::sum('qty'),
        ]);
    }

    // GET /wms/stocks/low-stock
    public function lowStock()
    {
        return response()->json(
            Stock::with('warehouse:id,name,code')
                ->whereColumn('qty', '<=', 'reorder_level')
                ->get()
        );
    }

    // GET /wms/stocks/movements
    public function movements()
    {
        return response()->json(
            \App\Models\StockMovement::with('createdBy')
                ->latest()
                ->paginate(50)
        );
    }

    // GET /wms/warehouses/{warehouse}/stocks
    public function byWarehouse(Warehouse $warehouse)
    {
        $stocks = Stock::where('warehouse_id', $warehouse->id)
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $stocks]);
    }

    // POST /wms/warehouses/{warehouse}/stocks
    public function store(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'uom'           => 'required|string|max:50',
            'qty'           => 'required|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
        ]);

        // Auto-generate SKU
        $sku = $this->generateSku($validated['name'], $warehouse->id);

        $stock = Stock::create([
            'warehouse_id'  => $warehouse->id,
            'name'          => $validated['name'],
            'sku'           => $sku,
            'uom'           => $validated['uom'],
            'qty'           => $validated['qty'],
            'reserved_qty'  => 0,
            'reorder_level' => $validated['reorder_level'] ?? 0,
        ]);

        return response()->json(['success' => true, 'data' => $stock], 201);
    }

    // PUT /wms/stocks/{stock}
    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'uom'           => 'sometimes|string|max:50',
            'qty'           => 'sometimes|numeric|min:0',
            'reorder_level' => 'sometimes|numeric|min:0',
        ]);

        $stock->update($validated);
        return response()->json(['success' => true, 'data' => $stock]);
    }

    // PATCH /wms/stocks/{stock}/adjust
    public function adjust(Request $request, Stock $stock)
    {
        $request->validate([
            'qty'   => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $stock->update(['qty' => $request->qty]);
        return response()->json(['success' => true, 'data' => $stock]);
    }

    // DELETE /wms/stocks/{stock}
    public function destroy(Stock $stock)
    {
        $stock->delete();
        return response()->json(['success' => true]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function generateSku(string $name, int $warehouseId): string
    {
        $words  = preg_split('/\s+/', trim($name));
        $prefix = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), $words)));
        $prefix = substr($prefix, 0, 4);

        $counter = 1;
        do {
            $sku    = $prefix . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
            $exists = Stock::where('warehouse_id', $warehouseId)->where('sku', $sku)->exists();
            $counter++;
        } while ($exists);

        return $sku;
    }
}