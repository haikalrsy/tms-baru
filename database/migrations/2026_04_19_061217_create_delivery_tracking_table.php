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
       Schema::create('delivery_tracking', function (Blueprint $table) {
    $table->id();
    $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
    $table->foreignId('driver_id')->constrained('users');
    $table->decimal('latitude', 10, 7);
    $table->decimal('longitude', 10, 7);
    $table->decimal('speed_kmh', 5, 1)->nullable();
    $table->decimal('heading', 5, 1)->nullable();
    $table->decimal('accuracy_m', 8, 2)->nullable();
    $table->enum('status_snapshot', [
        'waiting_assignment', 'assigned', 'accepted',
        'loading', 'in_transit', 'arrived', 'delivered',
        'pod_submitted', 'completed', 'failed', 'returned'
    ])->nullable();
    $table->timestamp('tracked_at')->useCurrent();
    $table->timestamps();
    $table->index(['delivery_order_id', 'tracked_at']);
    $table->index(['driver_id', 'tracked_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_tracking');
    }
};
