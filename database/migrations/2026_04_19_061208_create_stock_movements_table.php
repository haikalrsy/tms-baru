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
        Schema::create('stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('item_id')->constrained('items');
    $table->foreignId('rack_id')->constrained('racks');
    $table->enum('type', ['in', 'out', 'transfer', 'adjustment', 'opname']);
    $table->decimal('qty', 10, 2);
    $table->decimal('qty_before', 10, 2)->default(0);
    $table->decimal('qty_after', 10, 2)->default(0);
    $table->string('ref_type', 50)->nullable(); // GoodsReceipt, PickList, dll
    $table->unsignedBigInteger('ref_id')->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('moved_at')->useCurrent();
    $table->timestamps();
    $table->index(['item_id', 'moved_at']);
    $table->index(['ref_type', 'ref_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
