<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();

            // Relasi ke item master (nullable, bisa tambah stock manual)
            $table->foreignId('item_id')
                  ->nullable()
                  ->constrained('items')
                  ->nullOnDelete();

            // Warehouse
            $table->foreignId('warehouse_id')
                  ->nullable()
                  ->constrained('warehouses')
                  ->nullOnDelete();

            // Rack (nullable, stock bisa tanpa rack dulu)
            $table->foreignId('rack_id')
                  ->nullable()
                  ->constrained('racks')
                  ->nullOnDelete();

            // Info item
            $table->string('name', 200)->nullable();
            $table->string('sku', 50)->nullable();
            $table->string('uom', 20)->default('pcs');
            $table->string('batch_no', 100)->nullable();

            // Qty
            $table->decimal('qty', 10, 2)->default(0);
            $table->decimal('reserved_qty', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('warehouse_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};