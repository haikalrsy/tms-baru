<?php
namespace App\Services\Integration;

use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ERPSyncService
{
    // Dipanggil dari SyncSalesOrderController (via Python middleware)
    public function syncSalesOrders(array $orders): array
    {
        $created = 0; $updated = 0; $failed = 0; $errors = [];

        foreach ($orders as $order) {
            try {
                DB::transaction(function () use ($order, &$created, &$updated) {
                    $so = SalesOrder::updateOrCreate(
                        ['erp_id' => $order['erp_id']],
                        [
                            'so_number'   => $order['so_number'],
                            'customer_id' => Customer::where('erp_id', $order['customer_erp_id'])->value('id'),
                            'warehouse_id'=> $order['warehouse_id'] ?? 1,
                            'status'      => 'pending',
                            'delivery_date' => $order['delivery_date'] ?? null,
                            'last_synced_at' => now(),
                        ]
                    );

                    $isNew = $so->wasRecentlyCreated;

                    // Sync items
                    foreach ($order['items'] ?? [] as $item) {
                        SalesOrderItem::updateOrCreate(
                            ['so_id' => $so->id, 'item_id' => Item::where('erp_id', $item['item_erp_id'])->value('id')],
                            ['qty_ordered' => $item['qty']]
                        );
                    }

                    $isNew ? $created++ : $updated++;
                });
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "SO {$order['so_number']}: " . $e->getMessage();
                Log::error('[ERPSync] SO error: ' . $e->getMessage());
            }
        }

        return compact('created', 'updated', 'failed', 'errors');
    }

    public function syncCustomers(array $customers): array
    {
        $synced = 0;
        foreach ($customers as $c) {
            Customer::updateOrCreate(['erp_id' => $c['erp_id']], [
                'name'           => $c['name'],
                'code'           => $c['code'] ?? null,
                'phone'          => $c['phone'] ?? null,
                'email'          => $c['email'] ?? null,
                'address'        => $c['address'] ?? null,
                'city'           => $c['city'] ?? null,
                'last_synced_at' => now(),
            ]);
            $synced++;
        }
        return ['synced' => $synced];
    }

    public function syncItems(array $items): array
    {
        $synced = 0;
        foreach ($items as $i) {
            Item::updateOrCreate(['erp_id' => $i['erp_id']], [
                'sku'            => $i['sku'],
                'name'           => $i['name'],
                'uom'            => $i['uom'] ?? 'pcs',
                'category'       => $i['category'] ?? null,
                'weight_kg'      => $i['weight_kg'] ?? null,
                'last_synced_at' => now(),
            ]);
            $synced++;
        }
        return ['synced' => $synced];
    }
}