<?php
namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\DeliveryTracking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // GET /api/dashboard/summary
    public function summary(Request $request)
    {
        $date  = $request->date ?? today()->toDateString();
        $today = \Carbon\Carbon::parse($date);

        $summary = [
            'total_deliveries' => DeliveryOrder::whereDate('created_at', $today)->count(),
            'in_transit'       => DeliveryOrder::where('status', 'in_transit')->count(),
            'completed_today'  => DeliveryOrder::where('status', 'completed')->whereDate('delivered_at', $today)->count(),
            'failed_today'     => DeliveryOrder::where('status', 'failed')->whereDate('updated_at', $today)->count(),
            'waiting_assignment' => DeliveryOrder::where('status', 'waiting_assignment')->count(),
            'drivers_online'   => User::where('role', 'driver')->where('is_online', true)->count(),
        ];

        return response()->json(['success' => true, 'data' => $summary]);
    }

    // GET /api/dashboard/driver-locations
    public function driverLocations()
    {
        // Posisi terakhir semua driver yang sedang aktif
        $locations = DeliveryTracking::with(['driver:id,name', 'deliveryOrder:id,do_number,status'])
            ->whereIn('delivery_order_id', function ($query) {
                $query->select('id')->from('delivery_orders')
                    ->whereIn('status', ['in_transit', 'loading', 'arrived']);
            })
            ->whereRaw('id IN (
                SELECT MAX(id) FROM delivery_tracking
                WHERE tracked_at >= NOW() - INTERVAL 10 MINUTE
                GROUP BY driver_id
            )')
            ->get(['id', 'delivery_order_id', 'driver_id', 'latitude', 'longitude', 'speed_kmh', 'heading', 'tracked_at']);

        return response()->json(['success' => true, 'data' => $locations]);
    }
}