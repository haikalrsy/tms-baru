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
        Schema::create('goods_receipts', function (Blueprint $table) {
    $table->id();
    $table->string('gr_number', 50)->unique();
    $table->foreignId('so_id')->nullable()->constrained('sales_orders')->nullOnDelete();
    $table->foreignId('warehouse_id')->constrained('warehouses');
    $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
    $table->enum('status', ['draft', 'confirmed', 'putaway', 'completed'])->default('draft');
    $table->string('supplier_name', 200)->nullable();
    $table->text('notes')->nullable();
    $table->timestamp('received_at')->nullable();
    $table->timestamps();
});
 
Schema::create('goods_receipt_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('gr_id')->constrained('goods_receipts')->cascadeOnDelete();
    $table->foreignId('item_id')->constrained('items');
    $table->decimal('qty_expected', 10, 2);
    $table->decimal('qty_received', 10, 2)->default(0);
    $table->decimal('qty_good', 10, 2)->default(0);
    $table->decimal('qty_damaged', 10, 2)->default(0);
    $table->foreignId('rack_id')->nullable()->constrained('racks')->nullOnDelete();
    $table->string('batch_no', 100)->nullable();
    $table->date('expiry_date')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
