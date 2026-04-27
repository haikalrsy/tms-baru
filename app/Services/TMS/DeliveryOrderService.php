<?php
namespace App\Services\TMS;

use App\Models\DeliveryOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryOrderService
{
    private int $doCounter = 0;

    public function create(array $data, int $createdBy): DeliveryOrder
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $do = DeliveryOrder::create([
                ...$data,
                'do_number'  => $this->generateDoNumber(),
                'created_by' => $createdBy,
                'status'     => 'waiting_assignment',
            ]);

            ActivityLog::log('delivery.created', $do, [], $do->toArray(), $createdBy);
            return $do;
        });
    }

    public function assignDriver(int $doId, int $driverId, int $vehicleId): DeliveryOrder
    {
        $do      = DeliveryOrder::findOrFail($doId);
        $driver  = User::findOrFail($driverId);
        $vehicle = Vehicle::findOrFail($vehicleId);

        if ($do->status !== 'waiting_assignment') {
            throw ValidationException::withMessages([
                'status' => ['Delivery order must be in waiting_assignment status.']
            ]);
        }

        if (!$driver->isDriver() || !$driver->isApproved()) {
            throw ValidationException::withMessages([
                'driver_id' => ['Driver is not available or not approved.']
            ]);
        }

        if (!$vehicle->isAvailable()) {
            throw ValidationException::withMessages([
                'vehicle_id' => ['Vehicle is not available.']
            ]);
        }

        DB::transaction(function () use ($do, $driver, $vehicle) {
            $old = $do->toArray();
            $do->update([
                'driver_id'  => $driver->id,
                'vehicle_id' => $vehicle->id,
                'status'     => 'assigned',
            ]);
            $vehicle->update(['status' => 'on_trip']);

            ActivityLog::log('delivery.assigned', $do, $old, $do->fresh()->toArray());

            // TODO: kirim FCM ke driver
            // FcmService::notify($driver->fcm_token, 'New delivery assigned', $do->do_number);
        });

        return $do->fresh(['driver', 'vehicle']);
    }

    public function updateStatus(int $doId, string $newStatus, int $userId): DeliveryOrder
    {
        $do  = DeliveryOrder::findOrFail($doId);
        $old = $do->toArray();

        $timestamps = [
            'accepted'   => 'accepted_at',
            'in_transit' => 'departed_at',
            'arrived'    => 'arrived_at',
            'delivered'  => 'delivered_at',
        ];

        $update = ['status' => $newStatus];
        if (isset($timestamps[$newStatus])) {
            $update[$timestamps[$newStatus]] = now();
        }

        // Kalau selesai/failed — bebaskan vehicle
        if (in_array($newStatus, ['completed', 'failed', 'returned'])) {
            $do->vehicle?->update(['status' => 'available']);
        }

        $do->update($update);
        ActivityLog::log("delivery.status.{$newStatus}", $do, $old, $do->fresh()->toArray(), $userId);

        return $do->fresh();
    }

    private function generateDoNumber(): string
    {
        $prefix = 'DO-' . date('Ym') . '-';
        $last   = DeliveryOrder::where('do_number', 'like', $prefix . '%')->max('do_number');
        $next   = $last ? (int) substr($last, -5) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}