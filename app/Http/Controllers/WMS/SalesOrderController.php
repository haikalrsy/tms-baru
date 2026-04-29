<?php

namespace App\Http\Controllers\WMS;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesOrder::with(['items', 'createdBy'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('so_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name'      => 'required|string|max:255',
            'customer_code'      => 'nullable|string|max:100',
            'customer_address'   => 'nullable|string',
            'customer_phone'     => 'nullable|string|max:50',
            'customer_email'     => 'nullable|email|max:255',
            'order_date'         => 'required|date',
            'due_date'           => 'nullable|date|after_or_equal:order_date',
            'payment_terms'      => 'nullable|string|max:100',
            'notes'              => 'nullable|string',
            'discount'           => 'nullable|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.item_code'  => 'required|string|max:100',
            'items.*.item_name'  => 'required|string|max:255',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit'       => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes'      => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = collect($validated['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $discount = $validated['discount'] ?? 0;
            $total    = max(0, $subtotal - $discount);

            $salesOrder = SalesOrder::create([
                'so_number'        => 'SO-' . strtoupper(Str::random(8)),
                'customer_name'    => $validated['customer_name'],
                'customer_code'    => $validated['customer_code']    ?? null,
                'customer_address' => $validated['customer_address'] ?? null,
                'customer_phone'   => $validated['customer_phone']   ?? null,
                'customer_email'   => $validated['customer_email']   ?? null,
                'order_date'       => $validated['order_date'],
                'due_date'         => $validated['due_date']         ?? null,
                'payment_terms'    => $validated['payment_terms']    ?? null,
                'notes'            => $validated['notes']            ?? null,
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'total'            => $total,
                'status'           => 'draft',
                'created_by'       => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $salesOrder->items()->create([
                    'item_code'  => $item['item_code'],
                    'item_name'  => $item['item_name'],
                    'quantity'   => $item['quantity'],
                    'unit'       => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $item['quantity'] * $item['unit_price'],
                    'notes'      => $item['notes'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json($salesOrder->load('items'), 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sales order', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(SalesOrder $salesOrder)
    {
        return response()->json(
            $salesOrder->load(['items', 'createdBy', 'transferStocks.driver', 'transferStocks.destinationWarehouse'])
        );
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft sales orders can be updated'], 422);
        }

        $validated = $request->validate([
            'customer_name'      => 'sometimes|required|string|max:255',
            'customer_code'      => 'nullable|string|max:100',
            'customer_address'   => 'nullable|string',
            'customer_phone'     => 'nullable|string|max:50',
            'customer_email'     => 'nullable|email|max:255',
            'order_date'         => 'sometimes|required|date',
            'due_date'           => 'nullable|date',
            'payment_terms'      => 'nullable|string|max:100',
            'notes'              => 'nullable|string',
            'discount'           => 'nullable|numeric|min:0',
            'items'              => 'sometimes|array|min:1',
            'items.*.item_code'  => 'required_with:items|string|max:100',
            'items.*.item_name'  => 'required_with:items|string|max:255',
            'items.*.quantity'   => 'required_with:items|numeric|min:0.01',
            'items.*.unit'       => 'required_with:items|string|max:50',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.notes'      => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['items'])) {
                $salesOrder->items()->delete();
                $subtotal = 0;
                foreach ($validated['items'] as $item) {
                    $lineTotal = $item['quantity'] * $item['unit_price'];
                    $subtotal += $lineTotal;
                    $salesOrder->items()->create([
                        'item_code'  => $item['item_code'],
                        'item_name'  => $item['item_name'],
                        'quantity'   => $item['quantity'],
                        'unit'       => $item['unit'],
                        'unit_price' => $item['unit_price'],
                        'subtotal'   => $lineTotal,
                        'notes'      => $item['notes'] ?? null,
                    ]);
                }
                $discount              = $validated['discount'] ?? $salesOrder->discount;
                $validated['subtotal'] = $subtotal;
                $validated['total']    = max(0, $subtotal - $discount);
            }

            $salesOrder->update($validated);
            DB::commit();
            return response()->json($salesOrder->load('items'));

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update sales order', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft sales orders can be deleted'], 422);
        }

        $salesOrder->items()->delete();
        $salesOrder->delete();

        return response()->json(['message' => 'Sales order deleted']);
    }

    public function confirm(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft sales orders can be confirmed'], 422);
        }

        $salesOrder->update(['status' => 'confirmed']);
        return response()->json($salesOrder->load('items'));
    }
}