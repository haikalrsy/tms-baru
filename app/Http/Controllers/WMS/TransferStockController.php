<?php

namespace App\Http\Controllers\WMS;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Stock;
use App\Models\TransferStock;
use App\Models\WMS\SalesOrder;
use App\Models\WMS\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferStockController extends Controller
{
    /**
     * GET /wms/transfer-stocks
     * Admin: all transfers | Driver: only their own
     */
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = TransferStock::with([
            'salesOrder',
            'originWarehouse',
            'destinationWarehouse',
            'driver',
            'assignedBy',
        ])->latest();

        if ($user->role === 'driver') {
            $query->where('driver_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('warehouse_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('origin_warehouse_id', $request->warehouse_id)
                  ->orWhere('destination_warehouse_id', $request->warehouse_id);
            });
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * POST /wms/transfer-stocks
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id'            => 'required|exists:sales_orders,id',
            'origin_warehouse_id'       => 'required|exists:warehouses,id',
            'destination_warehouse_id'  => 'required|exists:warehouses,id|different:origin_warehouse_id',
            'driver_id'                 => 'required|exists:users,id',
            'scheduled_at'              => 'nullable|date',
            'notes'                     => 'nullable|string',
            'items'                     => 'nullable|array',
            'items.*.item_code'         => 'required_with:items|string|max:100',
            'items.*.item_name'         => 'required_with:items|string|max:255',
            'items.*.quantity'          => 'required_with:items|numeric|min:0.01',
            'items.*.unit'              => 'required_with:items|string|max:50',
        ]);

        $driverUser = User::findOrFail($validated['driver_id']);
        if ($driverUser->role !== 'driver') {
            return response()->json(['message' => 'Selected user is not a driver'], 422);
        }

        $driverProfile = $driverUser->driver;
        if (! $driverProfile || $driverProfile->availability_status !== 'available') {
            return response()->json([
                'message' => 'Driver is not available. Current status: ' . ($driverProfile?->availability_status ?? 'no profile'),
            ], 422);
        }

        $salesOrder = SalesOrder::findOrFail($validated['sales_order_id']);
        if (! in_array($salesOrder->status, ['confirmed', 'in_transfer'])) {
            return response()->json(['message' => 'Sales order must be confirmed before creating a transfer'], 422);
        }

        DB::beginTransaction();
        try {
            $transferStock = TransferStock::create([
                'transfer_number'          => 'TS-' . strtoupper(Str::random(8)),
                'sales_order_id'           => $validated['sales_order_id'],
                'origin_warehouse_id'      => $validated['origin_warehouse_id'],
                'destination_warehouse_id' => $validated['destination_warehouse_id'],
                'driver_id'                => $validated['driver_id'],
                'assigned_by'              => auth()->id(),
                'scheduled_at'             => $validated['scheduled_at'] ?? null,
                'notes'                    => $validated['notes'] ?? null,
                'status'                   => 'pending',
            ]);

            $items = $validated['items'] ?? $salesOrder->items->map(fn($i) => [
                'item_code' => $i->item_code,
                'item_name' => $i->item_name,
                'quantity'  => $i->quantity,
                'unit'      => $i->unit,
            ])->toArray();

            foreach ($items as $item) {
                $transferStock->items()->create($item);
            }

            $salesOrder->update(['status' => 'in_transfer']);

            DB::commit();

            return response()->json(
                $transferStock->load(['salesOrder', 'originWarehouse', 'destinationWarehouse', 'driver', 'items']),
                201
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create transfer stock', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /wms/transfer-stocks/{transferStock}
     */
    public function show(TransferStock $transferStock)
    {
        $user = auth()->user();

        if ($user->role === 'driver' && $transferStock->driver_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(
            $transferStock->load([
                'salesOrder.items',
                'originWarehouse',
                'destinationWarehouse',
                'driver',
                'assignedBy',
                'items',
                'trackings',
            ])
        );
    }

    /**
     * PATCH /wms/transfer-stocks/{transferStock}/status
     * Method lama dipertahankan (admin only, untuk cancel)
     */
    public function updateStatus(Request $request, TransferStock $transferStock)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'status'    => 'required|in:cancelled',
            'notes'     => 'nullable|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Only admin can cancel a transfer'], 403);
        }

        if ($transferStock->status === 'completed') {
            return response()->json(['message' => 'Cannot cancel a completed transfer'], 422);
        }

        DB::beginTransaction();
        try {
            $transferStock->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $salesOrder = $transferStock->salesOrder;
            if ($salesOrder && $salesOrder->status === 'in_transfer') {
                $salesOrder->update(['status' => 'confirmed']);
            }

            DB::commit();

            return response()->json($transferStock->load(['driver', 'originWarehouse', 'destinationWarehouse']));
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to cancel transfer', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /wms/transfer-stocks/{transferStock}/approve-putaway
     * BARU: Admin approve put away
     *  1. Set transfer → completed
     *  2. Buat Goods Receipt di destination warehouse
     *  3. Update stock on hand
     *  4. Kalau semua transfer SO sudah completed → hard delete SO dari WMS
     */
    public function approvePutAway(Request $request, TransferStock $transferStock)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Only admin can approve put away'], 403);
        }

        if ($transferStock->status !== 'put_away') {
            return response()->json([
                'message' => "Transfer must be in [put_away] status. Current: [{$transferStock->status}]",
            ], 422);
        }

        DB::beginTransaction();
        try {
            // ── 1. Set transfer → completed ──────────────────────────────────
            $transferStock->update([
                'status'               => 'completed',
                'put_away_approved_by' => auth()->id(),
                'put_away_approved_at' => now(),
                'delivered_at'         => now(),
            ]);

            // Driver kembali available
            $driver = $transferStock->driver;
            if ($driver && $driver->driver) {
                $driver->driver->availability_status = 'available';
                $driver->driver->save();
            }

            // ── 2. Buat Goods Receipt ────────────────────────────────────────
            $grNumber = 'GR-' . strtoupper(Str::random(8));

            $goodsReceipt = GoodsReceipt::create([
                'gr_number'     => $grNumber,
                'so_id'         => $transferStock->sales_order_id,
                'warehouse_id'  => $transferStock->destination_warehouse_id,
                'received_by'   => auth()->id(),
                'supplier_name' => null,
                'notes'         => "Auto-created from Transfer Stock #{$transferStock->transfer_number}",
                'status'        => 'completed',
                'received_at'   => now(),
            ]);

            // ── 3. Buat GR Items + Update Stock on Hand ──────────────────────
            $transferItems = $transferStock->items()->with('item')->get();

            foreach ($transferItems as $tsItem) {
                // Buat GR Item
                GoodsReceiptItem::create([
                    'gr_id'        => $goodsReceipt->id,
                    'item_id'      => $tsItem->item_id,
                    'qty_expected' => $tsItem->qty,
                    'qty_received' => $tsItem->qty,
                    'qty_good'     => $tsItem->qty,
                    'qty_damaged'  => 0,
                    'rack_id'      => null, // Rack belum ditentukan saat transfer
                    'batch_no'     => null,
                    'expiry_date'  => null,
                ]);

                // Update atau buat Stock on Hand di destination warehouse
                // Stock di-link ke rack, tapi karena rack_id null saat transfer,
                // kita cari stock existing berdasarkan item di warehouse tujuan
                // Kalau tidak ada, buat baru dengan rack_id null
                $stock = Stock::where('item_id', $tsItem->item_id)
                    ->whereNull('rack_id')
                    ->whereHas('rack', fn($q) => $q->where('warehouse_id', $transferStock->destination_warehouse_id))
                    ->first();

                // Kalau tidak ada rack, cari langsung by item saja
                // (fallback: cari stock yang rack-nya ada di destination warehouse)
                if (! $stock) {
                    $stock = Stock::whereHas('rack', function ($q) use ($transferStock) {
                        $q->where('warehouse_id', $transferStock->destination_warehouse_id);
                    })->where('item_id', $tsItem->item_id)->first();
                }

                if ($stock) {
                    $stock->increment('qty', $tsItem->qty);
                } else {
                    // Buat stock entry baru tanpa rack (perlu di-assign nanti)
                    Stock::create([
                        'item_id'      => $tsItem->item_id,
                        'rack_id'      => null,
                        'qty'          => $tsItem->qty,
                        'reserved_qty' => 0,
                        'batch_no'     => null,
                        'expiry_date'  => null,
                    ]);
                }
            }

            // ── 4. Cek apakah semua transfer SO sudah completed ──────────────
            $salesOrder   = $transferStock->salesOrder;
            $pendingCount = 0;

            if ($salesOrder) {
                $pendingCount = $salesOrder->transferStocks()
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->where('id', '!=', $transferStock->id)
                    ->count();
            }

            if ($salesOrder && $pendingCount === 0) {
                // Semua transfer selesai → hard delete SO dari WMS
                // SO akan dilanjutkan di TMS
                $salesOrder->delete();
            }

            DB::commit();

            return response()->json([
                'message'       => 'Put away approved. Goods receipt created.',
                'goods_receipt' => [
                    'id'        => $goodsReceipt->id,
                    'gr_number' => $goodsReceipt->gr_number,
                ],
                'so_deleted'    => $salesOrder && $pendingCount === 0,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to approve put away',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /wms/map/data
     * DIUPDATE: include status baru di filter transfers
     */
    public function mapData()
    {
        $warehouses = Warehouse::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_active', true)
            ->get(['id', 'name', 'code', 'address', 'city', 'country', 'latitude', 'longitude']);

        $drivers = User::where('role', 'driver')
            ->where('account_status', 'approved')
            ->whereHas('driver', fn($q) => $q->whereIn('availability_status', ['available', 'on_trip']))
            ->with('driver')
            ->get()
            ->map(fn($u) => [
                'id'                  => $u->id,
                'name'                => $u->name,
                'availability_status' => $u->driver?->availability_status,
                'lat'                 => $u->driver?->current_lat,
                'lng'                 => $u->driver?->current_lng,
                'last_location_at'    => $u->driver?->last_location_at,
            ]);

        // Include semua status aktif (exclude completed & cancelled)
        $transfers = TransferStock::with([
            'originWarehouse:id,name,latitude,longitude',
            'destinationWarehouse:id,name,latitude,longitude',
            'driver:id,name',
            'salesOrder:id,so_number',
            'trackings' => fn($q) => $q->orderBy('tracked_at', 'desc')->limit(50),
        ])
        ->whereIn('status', ['pending', 'picking', 'packing', 'on_the_way', 'put_away'])
        ->get();

        return response()->json([
            'warehouses' => $warehouses,
            'drivers'    => $drivers,
            'transfers'  => $transfers,
        ]);
    }
}