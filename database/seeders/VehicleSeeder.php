<?php
namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            ['plate_number' => 'B 1234 ABC', 'vehicle_type' => 'motor',    'brand' => 'Honda Beat',        'max_weight_kg' => 50,    'max_volume_m3' => 0.2],
            ['plate_number' => 'B 5678 DEF', 'vehicle_type' => 'pickup',   'brand' => 'Toyota Hilux',      'max_weight_kg' => 1000,  'max_volume_m3' => 2.5],
            ['plate_number' => 'B 9012 GHI', 'vehicle_type' => 'box',      'brand' => 'Mitsubishi Canter', 'max_weight_kg' => 4000,  'max_volume_m3' => 15],
            ['plate_number' => 'B 3456 JKL', 'vehicle_type' => 'truk',     'brand' => 'Isuzu Giga',        'max_weight_kg' => 15000, 'max_volume_m3' => 40],
            ['plate_number' => 'B 7890 MNO', 'vehicle_type' => 'tronton',  'brand' => 'Hino 500 Series',   'max_weight_kg' => 25000, 'max_volume_m3' => 60],
        ];

        foreach ($vehicles as $v) {
            Vehicle::firstOrCreate(['plate_number' => $v['plate_number']], array_merge($v, ['status' => 'available']));
        }

        $this->command->info('✅ Vehicles seeded');
    }
}