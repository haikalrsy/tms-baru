<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah 'confirmed' dan 'in_transfer' ke enum status sales_orders
        DB::statement("
            ALTER TABLE sales_orders
            MODIFY COLUMN status ENUM(
                'pending',
                'confirmed',
                'in_transfer',
                'processing',
                'picked',
                'packed',
                'shipped',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE sales_orders
            MODIFY COLUMN status ENUM(
                'pending',
                'processing',
                'picked',
                'packed',
                'shipped',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");
    }
};