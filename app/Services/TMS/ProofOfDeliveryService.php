<?php
namespace App\Services\TMS;

use App\Models\DeliveryOrder;
use App\Models\ProofOfDelivery;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProofOfDeliveryService
{
    public function submit(DeliveryOrder $do, Request $request): ProofOfDelivery
    {
        return DB::transaction(function () use ($do, $request) {
            $photoPath = $request->file('photo')
                ->store("pod/{$do->id}/photos", 'private');

            $sigPath = $request->file('signature')
                ->store("pod/{$do->id}/signatures", 'private');

            $pod = ProofOfDelivery::create([
                'delivery_order_id' => $do->id,
                'submitted_by'      => $request->user()->id,
                'recipient_name'    => $request->recipient_name,
                'recipient_title'   => $request->recipient_title,
                'photo_path'        => $photoPath,
                'signature_path'    => $sigPath,
                'notes'             => $request->notes,
                'status'            => 'submitted',
                'submitted_at'      => now(),
            ]);

            $do->update(['status' => 'pod_submitted']);
            ActivityLog::log('pod.submitted', $do);

            return $pod;
        });
    }
}