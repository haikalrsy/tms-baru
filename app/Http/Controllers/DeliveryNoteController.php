<?php

namespace App\Http\Controllers\TMS;
 
use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\DeliveryOrder;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
class DeliveryNoteController extends Controller
{
    // GET /api/tms/delivery-notes
    public function index(Request $request)
    {
        $list = DeliveryNote::with([
                'customer:id,name,code',
                'createdBy:id,name',
                'deliveryOrder:id,do_number,status',
            ])
            ->when($request->status,      fn($q, $v) => $q->where('status', $v))
            ->when($request->customer_id, fn($q, $v) => $q->where('customer_id', $v))
            ->when($request->date_from,   fn($q, $v) => $q->whereDate('delivery_date', '>=', $v))
            ->when($request->date_to,     fn($q, $v) => $q->whereDate('delivery_date', '<=', $v))
            ->when($request->search,      fn($q, $v) =>
                $q->where('dn_number', 'like', "%$v%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%$v%"))
                  ->orWhere('receiver_name', 'like', "%$v%")
            )
            ->latest()
            ->paginate($request->per_page ?? 20);
 
        return response()->json(['success' => true, 'data' => $list]);
    }
 
    // POST /api/tms/delivery-notes
    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_order_id' => 'required|exists:delivery_orders,id',
            'delivery_date'     => 'required|date',
            'shipper_name'      => 'nullable|string|max:200',
            'shipper_address'   => 'nullable|string',
            'receiver_name'     => 'required|string|max:200',
            'receiver_address'  => 'required|string',
            'receiver_phone'    => 'nullable|string|max:20',
            'cargo_description' => 'nullable|string',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.item_id'       => 'nullable|exists:items,id',
            'items.*.item_name'     => 'required|string|max:200',
            'items.*.item_sku'      => 'nullable|string|max:50',
            'items.*.uom'           => 'nullable|string|max:20',
            'items.*.qty'           => 'required|numeric|min:0.01',
            'items.*.weight_kg'     => 'nullable|numeric|min:0',
            'items.*.package_type'  => 'nullable|string|max:50',
            'items.*.batch_no'      => 'nullable|string|max:100',
            'items.*.box_count'     => 'nullable|integer|min:1',
        ]);
 
        $dn = DB::transaction(function () use ($validated, $request) {
            // Ambil info DO untuk snapshot vehicle & driver
            $do = DeliveryOrder::with(['vehicle', 'driver'])->findOrFail($validated['delivery_order_id']);
 
            // Hitung totals dari items
            $totalPkg    = count($validated['items']);
            $totalWeight = collect($validated['items'])->sum('weight_kg');
            $totalBoxes  = collect($validated['items'])->sum(fn($i) => $i['box_count'] ?? 1);
 
            $dn = DeliveryNote::create([
                'dn_number'         => DeliveryNote::generateNumber(),
                'delivery_order_id' => $do->id,
                'customer_id'       => $do->customer_id,
                'created_by'        => $request->user()->id,
                'delivery_date'     => $validated['delivery_date'],
                'shipper_name'      => $validated['shipper_name'] ?? config('app.name'),
                'shipper_address'   => $validated['shipper_address'] ?? null,
                'receiver_name'     => $validated['receiver_name'],
                'receiver_address'  => $validated['receiver_address'],
                'receiver_phone'    => $validated['receiver_phone'] ?? null,
                // Snapshot kendaraan & driver dari DO
                'vehicle_plate'     => $do->vehicle?->plate_number,
                'vehicle_type'      => $do->vehicle?->vehicle_type,
                'driver_name'       => $do->driver?->name,
                'driver_phone'      => $do->driver?->driver?->phone,
                'total_packages'    => $totalPkg,
                'total_weight_kg'   => $totalWeight,
                'total_volume_m3'   => 0,
                'cargo_description' => $validated['cargo_description'] ?? null,
                'notes'             => $validated['notes'] ?? null,
                'status'            => 'draft',
            ]);
 
            // Create items
            foreach ($validated['items'] as $item) {
                $dn->items()->create([
                    'item_id'      => $item['item_id'] ?? null,
                    'item_name'    => $item['item_name'],
                    'item_sku'     => $item['item_sku'] ?? null,
                    'uom'          => $item['uom'] ?? 'pcs',
                    'qty'          => $item['qty'],
                    'weight_kg'    => $item['weight_kg'] ?? null,
                    'package_type' => $item['package_type'] ?? null,
                    'batch_no'     => $item['batch_no'] ?? null,
                    'box_count'    => $item['box_count'] ?? 1,
                ]);
            }
 
            ActivityLog::log('delivery_note.created', $dn, [], [], $request->user()->id);
            return $dn;
        });
 
        return response()->json([
            'success' => true,
            'message' => 'Delivery note created.',
            'data'    => $dn->load(['items', 'customer', 'deliveryOrder']),
        ], 201);
    }
 
    // GET /api/tms/delivery-notes/{id}
    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load([
            'items.item:id,sku,name',
            'customer',
            'deliveryOrder.vehicle',
            'deliveryOrder.driver',
            'createdBy:id,name',
        ]);
        return response()->json(['success' => true, 'data' => $deliveryNote]);
    }
 
    // PUT /api/tms/delivery-notes/{id}
    public function update(Request $request, DeliveryNote $deliveryNote)
    {
        if (!$deliveryNote->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft delivery notes can be edited.',
            ], 422);
        }
 
        $validated = $request->validate([
            'delivery_date'     => 'sometimes|date',
            'receiver_name'     => 'sometimes|string|max:200',
            'receiver_address'  => 'sometimes|string',
            'receiver_phone'    => 'nullable|string|max:20',
            'shipper_name'      => 'nullable|string|max:200',
            'shipper_address'   => 'nullable|string',
            'cargo_description' => 'nullable|string',
            'notes'             => 'nullable|string',
            'items'             => 'sometimes|array|min:1',
            'items.*.item_id'      => 'nullable|exists:items,id',
            'items.*.item_name'    => 'required_with:items|string|max:200',
            'items.*.qty'          => 'required_with:items|numeric|min:0.01',
            'items.*.weight_kg'    => 'nullable|numeric|min:0',
            'items.*.package_type' => 'nullable|string|max:50',
            'items.*.box_count'    => 'nullable|integer|min:1',
            'items.*.uom'          => 'nullable|string|max:20',
        ]);
 
        DB::transaction(function () use ($validated, $deliveryNote) {
            $deliveryNote->update(collect($validated)->except('items')->toArray());
 
            if (isset($validated['items'])) {
                $deliveryNote->items()->delete();
                foreach ($validated['items'] as $item) {
                    $deliveryNote->items()->create([
                        'item_id'      => $item['item_id'] ?? null,
                        'item_name'    => $item['item_name'],
                        'item_sku'     => $item['item_sku'] ?? null,
                        'uom'          => $item['uom'] ?? 'pcs',
                        'qty'          => $item['qty'],
                        'weight_kg'    => $item['weight_kg'] ?? null,
                        'package_type' => $item['package_type'] ?? null,
                        'batch_no'     => $item['batch_no'] ?? null,
                        'box_count'    => $item['box_count'] ?? 1,
                    ]);
                }
                // Recalculate totals
                $deliveryNote->update([
                    'total_packages'  => count($validated['items']),
                    'total_weight_kg' => collect($validated['items'])->sum('weight_kg'),
                ]);
            }
        });
 
        return response()->json([
            'success' => true,
            'message' => 'Delivery note updated.',
            'data'    => $deliveryNote->fresh(['items', 'customer']),
        ]);
    }
 
    // POST /api/tms/delivery-notes/{id}/issue — ubah status draft → issued
    public function issue(Request $request, DeliveryNote $deliveryNote)
    {
        if (!$deliveryNote->isDraft()) {
            return response()->json(['success' => false, 'message' => 'Already issued.'], 422);
        }
 
        $deliveryNote->update(['status' => 'issued', 'issued_at' => now()]);
        ActivityLog::log('delivery_note.issued', $deliveryNote, [], [], $request->user()->id);
 
        return response()->json(['success' => true, 'message' => 'Delivery note issued.', 'data' => $deliveryNote]);
    }
 
    // DELETE /api/tms/delivery-notes/{id}
    public function destroy(DeliveryNote $deliveryNote)
    {
        if (!$deliveryNote->isDraft()) {
            return response()->json(['success' => false, 'message' => 'Only draft can be deleted.'], 422);
        }
        $deliveryNote->delete();
        return response()->json(['success' => true, 'message' => 'Delivery note deleted.']);
    }
 
    // GET /api/tms/delivery-notes/{id}/print — data untuk print/PDF
    public function print(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['items.item', 'customer', 'deliveryOrder', 'createdBy:id,name']);
        return response()->json(['success' => true, 'data' => $deliveryNote]);
    }
}
?>
