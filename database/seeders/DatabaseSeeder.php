<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            WarehouseSeeder::class,
            VehicleSeeder::class,
            // CustomerSeeder::class, // (Uncomment jika CustomerSeeder sudah dibuat)
            // ItemSeeder::class,     // (Uncomment jika ItemSeeder sudah dibuat)
        ]);
    }
}