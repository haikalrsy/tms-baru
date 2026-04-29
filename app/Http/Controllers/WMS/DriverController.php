<?php

namespace App\Http\Controllers\WMS;

use App\Http\Controllers\Controller;
use App\Models\TransferStock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DriverController extends Controller
{
    // GET /api/driver/status
    public function status()
    {
        $user   = Auth::user();
        $driver = $user->driver;

        return response()->json([
            'availability_status' => $driver?->availability_status ?? 'off_duty',
            'current_lat'         => $driver?->current_lat,
            'current_lng'         => $driver?->current_lng,
            'last_location_at'    => $driver?->last_location_at,
        ]);
    }

    // PUT /api/driver/status
    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:available,on_trip,off_duty,rest',
        ]);

        $user   = Auth::user();
        $driver = $user->driver;

        if (! $driver) {
            $driver = $user->driver()->create([
                'availability_status' => $request->status,
                'current_lat'         => null,
                'current_lng'         => null,
                'last_location_at'    => null,
            ]);
        } else {
            $driver->availability_status = $request->status;
            $driver->save();
        }

        return response()->json([
            'message'             => 'Status updated',
            'availability_status' => $driver->availability_status,
        ]);
    }

    // PUT /api/driver/location
    public function updateLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $user   = Auth::user();
        $driver = $user->driver;

        if (! $driver) {
            $user->driver()->create([
                'availability_status' => 'available',
                'current_lat'         => $request->lat,
                'current_lng'         => $request->lng,
                'last_location_at'    => now(),
            ]);
            return response()->json(['message' => 'Location updated']);
        }

        if ($driver->availability_status === 'off_duty') {
            return response()->json(['message' => 'Driver is not active'], 403);
        }

        $driver->current_lat      = $request->lat;
        $driver->current_lng      = $request->lng;
        $driver->last_location_at = now();
        $driver->save();

        return response()->json(['message' => 'Location updated']);
    }

    // GET /api/driver/transfers
    public function transfers(Request $request)
    {
        try {
            $user = Auth::user();

            $query = TransferStock::where('driver_id', $user->id)
                ->with([
                    'originWarehouse:id,name,address,latitude,longitude',
                    'destinationWarehouse:id,name,address,latitude,longitude',
                    'items',
                    'salesOrder:id,so_number',
                ]);

            if ($request->filled('status')) {
                $statuses = array_map('trim', explode(',', $request->status));
                $query->whereIn('status', $statuses);
            }

            // CASE WHEN — works on MySQL, PostgreSQL, SQLite
            $query->orderByRaw("
                CASE status
                    WHEN 'picking'    THEN 1
                    WHEN 'packing'    THEN 2
                    WHEN 'on_the_way' THEN 3
                    WHEN 'put_away'   THEN 4
                    WHEN 'completed'  THEN 5
                    WHEN 'cancelled'  THEN 6
                    ELSE 7
                END
            ")->orderBy('created_at', 'desc');

            return response()->json($query->paginate(20));

        } catch (\Throwable $e) {
            Log::error('DriverController@transfers: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // GET /api/driver/transfers/pending
    public function pendingTransfers()
    {
        try {
            $user = Auth::user();

            $pending = TransferStock::where('driver_id', $user->id)
                ->whereIn('status', ['picking', 'packing', 'on_the_way', 'put_away'])
                ->whereNull('driver_rejected_at')
                ->with([
                    'originWarehouse:id,name',
                    'destinationWarehouse:id,name',
                    'items',
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($t) => [
                    'id'              => $t->id,
                    'transfer_number' => $t->transfer_number,
                    'origin'          => $t->originWarehouse?->name ?? '-',
                    'destination'     => $t->destinationWarehouse?->name ?? '-',
                    'items_count'     => $t->items->count(),
                    'status'          => $t->status,
                    'created_at'      => $t->created_at,
                ]);

            return response()->json(['data' => $pending, 'total' => $pending->count()]);

        } catch (\Throwable $e) {
            Log::error('DriverController@pendingTransfers: ' . $e->getMessage());
            return response()->json(['data' => [], 'total' => 0]);
        }
    }

    // GET /api/driver/transfers/{id}
    public function showTransfer($id)
    {
        $user = Auth::user();

        $transfer = TransferStock::where('driver_id', $user->id)
            ->where('id', $id)
            ->with(['originWarehouse', 'destinationWarehouse', 'items', 'salesOrder'])
            ->firstOrFail();

        return response()->json($transfer);
    }

    // POST /api/driver/transfers/{id}/reject
    public function rejectTransfer(Request $request, $id)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);

        $user     = Auth::user();
        $transfer = TransferStock::where('driver_id', $user->id)
            ->where('id', $id)
            ->whereIn('status', ['picking', 'pending'])
            ->firstOrFail();

        $transfer->driver_id          = null;
        $transfer->driver_rejected_at = now();
        $transfer->rejection_reason   = $request->reason ?? 'Driver rejected';
        $transfer->status             = 'pending';
        $transfer->save();

        return response()->json(['message' => 'Transfer rejected, admin will reassign']);
    }

    // POST /api/driver/transfers/{id}/status
    // picking → packing → on_the_way → put_away
    public function updateTransferStatus(Request $request, $id)
    {
        $request->validate([
            'notes'     => 'nullable|string',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $user   = Auth::user();
        $driver = $user->driver;

        $transfer = TransferStock::where('driver_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $allowed = [
            'picking'    => 'packing',
            'packing'    => 'on_the_way',
            'on_the_way' => 'put_away',
        ];

        if (! isset($allowed[$transfer->status])) {
            return response()->json([
                'message' => "Status [{$transfer->status}] cannot be advanced by driver",
            ], 422);
        }

        $next = $allowed[$transfer->status];

        $timestamps = [
            'packing'    => 'picked_at',
            'on_the_way' => 'packed_at',
            'put_away'   => 'on_the_way_at',
        ];

        $data = ['status' => $next];
        if (isset($timestamps[$next])) $data[$timestamps[$next]] = now();
        if ($next === 'put_away')      $data['put_away_at']       = now();

        $transfer->update($data);

        // Tracking — optional, tidak crash kalau tabel tidak ada
        try {
            $transfer->trackings()->create([
                'status'     => $next,
                'notes'      => $request->notes ?? "Driver updated to {$next}",
                'latitude'   => $request->latitude  ?? $driver?->current_lat,
                'longitude'  => $request->longitude ?? $driver?->current_lng,
                'tracked_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Tracking insert skipped: ' . $e->getMessage());
        }

        return response()->json([
            'message'     => "Status updated to [{$next}]",
            'transfer'    => $transfer->fresh(['originWarehouse', 'destinationWarehouse', 'items']),
            'next_action' => $next === 'put_away' ? 'Waiting for admin approval' : null,
        ]);
    }

    // POST /api/driver/transfers/{id}/deliver (backward compat)
    public function deliverTransfer(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string',
            'lat'   => 'nullable|numeric',
            'lng'   => 'nullable|numeric',
        ]);

        $user     = Auth::user();
        $driver   = $user->driver;
        $transfer = TransferStock::where('driver_id', $user->id)
            ->where('id', $id)
            ->where('status', 'on_the_way')
            ->firstOrFail();

        $transfer->update(['status' => 'put_away', 'put_away_at' => now()]);

        try {
            $transfer->trackings()->create([
                'status'     => 'put_away',
                'notes'      => $request->notes ?? 'Arrived at destination',
                'latitude'   => $request->lat ?? $driver?->current_lat,
                'longitude'  => $request->lng ?? $driver?->current_lng,
                'tracked_at' => now(),
            ]);
        } catch (\Throwable $e) { /* silent */ }

        return response()->json(['message' => 'Arrived at destination. Waiting for admin to approve put away.']);
    }

    // GET /api/admin/drivers/online
    public function onlineDrivers()
    {
        $drivers = User::where('role', 'driver')
            ->where('account_status', 'approved')
            ->with('driver')
            ->get()
            ->map(fn($u) => [
                'id'                  => $u->id,
                'name'                => $u->name,
                'email'               => $u->email,
                'availability_status' => $u->driver?->availability_status ?? 'off_duty',
                'current_lat'         => $u->driver?->current_lat,
                'current_lng'         => $u->driver?->current_lng,
                'last_location_at'    => $u->driver?->last_location_at,
                'is_available'        => $u->driver?->availability_status === 'available',
            ]);

        return response()->json(['data' => $drivers]);
    }
}