<?php
namespace App\Http\Controllers\TMS;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Services\TMS\DeliveryOrderService;
use Illuminate\Http\Request;

class DeliveryOrderController extends Controller
{
    public function __construct(private DeliveryOrderService $service) {}

    // GET /api/tms/delivery-orders
    public function index(Request $request)
    {
        $query = DeliveryOrder::with(['customer:id,name', 'driver:id,name', 'vehicle:id,plate_number'])
            ->when($request->status,    fn($q, $v) => $q->where('status', $v))
            ->when($request->driver_id, fn($q, $v) => $q->where('driver_id', $v))
            ->when($request->date,      fn($q, $v) => $q->whereDate('created_at', $v))
            ->when($request->search,    fn($q, $v) =>
                $q->where('do_number', 'like', "%$v%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%$v%"))
            )
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $query]);
    }

    // POST /api/tms/delivery-orders
    public function store(Request $request)
    {
        $validated = $request->validate([
            'so_id'               => 'nullable|exists:sales_orders,id',
            'customer_id'         => 'required|exists:customers,id',
            'destination_address' => 'required|string',
            'destination_lat'     => 'nullable|numeric',
            'destination_lng'     => 'nullable|numeric',
            'total_weight_kg'     => 'nullable|numeric',
            'notes'               => 'nullable|string',
            'scheduled_at'        => 'nullable|date',
        ]);

        $do = $this->service->create($validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Delivery order created.',
            'data'    => $do->load(['customer', 'createdBy:id,name']),
        ], 201);
    }

    // GET /api/tms/delivery-orders/{id}
    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['customer', 'driver.driver', 'vehicle', 'salesOrder', 'pod', 'routePlan', 'latestTracking']);
        return response()->json(['success' => true, 'data' => $deliveryOrder]);
    }

    // POST /api/tms/delivery-orders/{id}/assign
    public function assign(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'driver_id'  => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $do = $this->service->assignDriver(
            $deliveryOrder->id,
            $request->driver_id,
            $request->vehicle_id
        );

        return response()->json(['success' => true, 'message' => 'Driver assigned. Notification sent.', 'data' => $do]);
    }

    // PATCH /api/tms/delivery-orders/{id}/status
    public function updateStatus(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'status' => 'required|in:accepted,loading,in_transit,arrived,delivered,failed,returned',
        ]);

        // Driver hanya bisa update DO yang ditugaskan kepadanya
        if ($request->user()->isDriver() && $deliveryOrder->driver_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $do = $this->service->updateStatus($deliveryOrder->id, $request->status, $request->user()->id);

        return response()->json(['success' => true, 'data' => $do]);
    }

    // GET /api/tms/delivery-orders/{id}/pod
    public function getPod(DeliveryOrder $deliveryOrder)
    {
        $pod = $deliveryOrder->pod;
        if (!$pod) {
            return response()->json(['success' => false, 'message' => 'POD not submitted yet.'], 404);
        }
        return response()->json(['success' => true, 'data' => [
            'id'             => $pod->id,
            'recipient_name' => $pod->recipient_name,
            'status'         => $pod->status,
            'submitted_at'   => $pod->submitted_at,
            'photo_url'      => $pod->photo_url,
            'signature_url'  => $pod->signature_url,
        ]]);
    }
}