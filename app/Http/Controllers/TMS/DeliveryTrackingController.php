<?php
namespace App\Http\Controllers\TMS;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\DeliveryTracking;
use Illuminate\Http\Request;

class DeliveryTrackingController extends Controller
{
    // POST /api/tms/delivery-orders/{id}/tracking
    public function update(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed'     => 'nullable|numeric|min:0',
            'heading'   => 'nullable|numeric|between:0,360',
        ]);

        if ($deliveryOrder->driver_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        DeliveryTracking::create([
            'delivery_order_id' => $deliveryOrder->id,
            'driver_id'         => $request->user()->id,
            'latitude'          => $request->latitude,
            'longitude'         => $request->longitude,
            'speed_kmh'         => $request->speed,
            'heading'           => $request->heading,
            'status_snapshot'   => $deliveryOrder->status,
            'tracked_at'        => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Location updated.']);
    }

    // GET /api/tms/delivery-orders/{id}/tracking
    public function history(DeliveryOrder $deliveryOrder)
    {
        $history = $deliveryOrder->tracking()
            ->orderBy('tracked_at', 'asc')
            ->get(['latitude', 'longitude', 'speed_kmh', 'heading', 'tracked_at']);

        return response()->json(['success' => true, 'data' => $history]);
    }
}