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
       Schema::create('pick_lists', function (Blueprint $table) {
    $table->id();
    $table->string('pl_number', 50)->unique();
    $table->foreignId('so_id')->constrained('sales_orders');
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
 
Schema::create('pick_list_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pick_list_id')->constrained('pick_lists')->cascadeOnDelete();
    $table->foreignId('item_id')->constrained('items');
    $table->foreignId('rack_id')->constrained('racks');
    $table->decimal('qty_required', 10, 2);
    $table->decimal('qty_picked', 10, 2)->default(0);
    $table->enum('status', ['pending', 'picked', 'short'])->default('pending');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pick_lists');
    }
};
