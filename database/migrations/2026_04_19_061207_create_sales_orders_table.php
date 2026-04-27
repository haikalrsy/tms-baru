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
        Schema::create('sales_orders', function (Blueprint $table) {
    $table->id();
    $table->string('so_number', 50)->unique();
    $table->string('erp_id', 50)->nullable()->unique();
    $table->foreignId('customer_id')->constrained();
    $table->foreignId('warehouse_id')->constrained();
    $table->enum('status', ['pending', 'processing', 'picked', 'packed', 'shipped', 'completed', 'cancelled'])->default('pending');
    $table->date('delivery_date')->nullable();
    $table->text('notes')->nullable();
    $table->timestamp('last_synced_at')->nullable();
    $table->timestamps();
});
 
Schema::create('sales_order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('so_id')->constrained('sales_orders')->cascadeOnDelete();
    $table->foreignId('item_id')->constrained('items');
    $table->decimal('qty_ordered', 10, 2);
    $table->decimal('qty_delivered', 10, 2)->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
