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
        Schema::create('items', function (Blueprint $table) {
    $table->id();
    $table->string('erp_id', 50)->nullable()->unique();
    $table->string('sku', 50)->unique();
    $table->string('name', 200);
    $table->string('uom', 20)->default('pcs'); // unit of measure
    $table->string('category', 100)->nullable();
    $table->decimal('weight_kg', 10, 3)->nullable();
    $table->decimal('volume_m3', 10, 4)->nullable();
    $table->integer('min_stock_alert')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_synced_at')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
