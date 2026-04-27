<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin default
        $admin = User::firstOrCreate(['email' => 'admin@logistics.local'], [
            'name'              => 'Admin Logistics',
            'password'          => Hash::make('Admin@2024!'),
            'role'              => 'admin',
            'account_status'    => 'approved',
            'approved_at'       => now(),
            'email_verified_at' => now(),
        ]);

        // Driver contoh
        $drivers = [
            ['email' => 'driver1@logistics.local', 'name' => 'Budi Santoso', 'license' => 'SIM B1', 'phone' => '08111111111'],
            ['email' => 'driver2@logistics.local', 'name' => 'Joko Widodo',  'license' => 'SIM B2', 'phone' => '08222222222'],
            ['email' => 'driver3@logistics.local', 'name' => 'Siti Rahayu',  'license' => 'SIM A',  'phone' => '08333333333'],
        ];

        foreach ($drivers as $d) {
            $user = User::firstOrCreate(['email' => $d['email']], [
                'name'              => $d['name'],
                'password'          => Hash::make('Driver@123!'),
                'role'              => 'driver',
                'account_status'    => 'approved',
                'approved_by'       => $admin->id,
                'approved_at'       => now(),
                'email_verified_at' => now(),
            ]);

            Driver::firstOrCreate(['user_id' => $user->id], [
                'license_type'        => $d['license'],
                'phone'               => $d['phone'],
                'availability_status' => 'available',
            ]);
        }

        $this->command->info('✅ Users & Drivers seeded');
    }
}