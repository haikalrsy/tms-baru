<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Transfer Stocks ──────────────────────────────────────────────────
        Schema::create('transfer_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('origin_warehouse_id')->constrained('warehouses');
            $table->foreignId('destination_warehouse_id')->constrained('warehouses');
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'pending', 'picking', 'packing', 'on_the_way',
                'put_away', 'completed', 'cancelled',
            ])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('on_the_way_at')->nullable();
            $table->timestamp('put_away_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('driver_confirmed_at')->nullable();
            $table->timestamp('driver_rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreignId('put_away_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('put_away_approved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('driver_id');
        });

        // ── Transfer Stock Items ─────────────────────────────────────────────
        Schema::create('transfer_stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('qty', 15, 4);
            $table->string('unit', 50);
            $table->timestamps();
        });

        // ── Transfer Stock Trackings ─────────────────────────────────────────
        Schema::create('transfer_stock_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_stock_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('status', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('tracked_at');
            $table->timestamps();

            $table->index(['transfer_stock_id', 'tracked_at']);
        });

        // ── Tambah kolom koordinat ke warehouses (jika belum ada) ───────────
        Schema::table('warehouses', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouses', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'city')) {
                $table->string('city')->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'country')) {
                $table->string('country')->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_stock_trackings');
        Schema::dropIfExists('transfer_stock_items');
        Schema::dropIfExists('transfer_stocks');

        Schema::table('warehouses', function (Blueprint $table) {
            foreach (['latitude', 'longitude', 'city', 'country', 'is_active'] as $col) {
                if (Schema::hasColumn('warehouses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};