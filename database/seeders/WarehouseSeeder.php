<?php
namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\Zone;
use App\Models\Rack;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['name' => 'Gudang Jakarta Utara', 'code' => 'JKT', 'city' => 'Jakarta', 'latitude' => -6.1376, 'longitude' => 106.8493],
            ['name' => 'Gudang Bekasi',        'code' => 'BKS', 'city' => 'Bekasi',  'latitude' => -6.2349, 'longitude' => 107.0004],
        ];

        foreach ($warehouses as $whData) {
            $wh = Warehouse::firstOrCreate(['code' => $whData['code']], array_merge($whData, ['status' => 'active']));

            $zones = [
                ['name' => 'Receiving',  'code' => 'RCV', 'type' => 'receiving'],
                ['name' => 'Storage A',  'code' => 'STA', 'type' => 'storage'],
                ['name' => 'Storage B',  'code' => 'STB', 'type' => 'storage'],
                ['name' => 'Staging',    'code' => 'STG', 'type' => 'staging'],
                ['name' => 'Shipping',   'code' => 'SHP', 'type' => 'shipping'],
            ];

            foreach ($zones as $zoneData) {
                $zone = Zone::firstOrCreate(['warehouse_id' => $wh->id, 'code' => $zoneData['code']],
                    array_merge($zoneData, ['warehouse_id' => $wh->id]));

                if ($zoneData['type'] === 'storage') {
                    for ($r = 1; $r <= 5; $r++) {
                        $code = 'R' . str_pad($r, 2, '0', STR_PAD_LEFT);
                        Rack::firstOrCreate(['zone_id' => $zone->id, 'code' => $code], [
                            'zone_id'       => $zone->id,
                            'code'          => $code,
                            'full_code'     => "{$wh->code}-{$zone->code}-{$code}",
                            'total_levels'  => 4,
                            'max_weight_kg' => 1000,
                            'status'        => 'available',
                        ]);
                    }
                }
            }
        }

        $this->command->info('✅ Warehouses, Zones & Racks seeded');
    }
}