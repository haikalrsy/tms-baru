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
        Schema::create('packings', function (Blueprint $table) {
    $table->id();
    $table->string('packing_number', 50)->unique();
    $table->foreignId('so_id')->constrained('sales_orders');
    $table->foreignId('pick_list_id')->nullable()->constrained('pick_lists')->nullOnDelete();
    $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
    $table->integer('total_boxes')->default(0);
    $table->decimal('total_weight_kg', 10, 2)->default(0);
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
Schema::create('packing_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('packing_id')->constrained('packings')->cascadeOnDelete();
    $table->foreignId('item_id')->constrained('items');
    $table->decimal('qty', 10, 2);
    $table->string('box_label', 50)->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packings');
    }
};
