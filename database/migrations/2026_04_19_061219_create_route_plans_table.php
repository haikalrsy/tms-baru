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
       Schema::create('route_plans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
    $table->decimal('total_distance_km', 10, 2)->nullable();
    $table->decimal('estimated_duration_hours', 5, 1)->nullable();
    $table->json('waypoints')->nullable();
    $table->json('route_geometry')->nullable(); // GeoJSON
    $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_plans');
    }
};
