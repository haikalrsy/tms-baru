<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
    $table->id();
    $table->string('do_number', 50)->unique();
    $table->foreignId('so_id')->nullable()->constrained('sales_orders')->nullOnDelete();
    $table->foreignId('customer_id')->constrained('customers');
    $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->enum('status', [
        'waiting_assignment', 'assigned', 'accepted',
        'loading', 'in_transit', 'arrived', 'delivered',
        'pod_submitted', 'completed', 'failed', 'returned'
    ])->default('waiting_assignment');
    $table->text('origin_address')->nullable();
    $table->decimal('origin_lat', 10, 7)->nullable();
    $table->decimal('origin_lng', 10, 7)->nullable();
    $table->text('destination_address');
    $table->decimal('destination_lat', 10, 7)->nullable();
    $table->decimal('destination_lng', 10, 7)->nullable();
    $table->decimal('total_weight_kg', 10, 2)->nullable();
    $table->text('notes')->nullable();
    $table->timestamp('scheduled_at')->nullable();
    $table->timestamp('accepted_at')->nullable();
    $table->timestamp('departed_at')->nullable();
    $table->timestamp('arrived_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['status', 'driver_id']);
    $table->index('created_at');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
