<?php
namespace App\Http\Controllers\TMS;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\ProofOfDelivery;
use App\Services\TMS\ProofOfDeliveryService;
use Illuminate\Http\Request;

class ProofOfDeliveryController extends Controller
{
    public function __construct(private ProofOfDeliveryService $service) {}

    // POST /api/tms/delivery-orders/{id}/pod
    public function store(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'photo'          => 'required|image|max:5120',
            'signature'      => 'required|image|max:2048',
            'recipient_name' => 'required|string|max:100',
            'recipient_title'=> 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:500',
        ]);

        if ($deliveryOrder->driver_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if ($deliveryOrder->status !== 'arrived') {
            return response()->json(['success' => false, 'message' => 'DO must be in arrived status.'], 422);
        }

        $pod = $this->service->submit($deliveryOrder, $request);

        return response()->json([
            'success' => true,
            'message' => 'POD submitted successfully.',
            'data'    => $pod,
        ], 201);
    }

    // PATCH /api/tms/delivery-orders/{id}/pod/verify
    public function verify(Request $request, DeliveryOrder $deliveryOrder)
    {
        $pod = $deliveryOrder->pod;
        if (!$pod || $pod->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'No POD to verify.'], 422);
        }

        $pod->update([
            'status'      => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $deliveryOrder->update(['status' => 'completed']);

        return response()->json(['success' => true, 'message' => 'POD verified. DO completed.']);
    }
}