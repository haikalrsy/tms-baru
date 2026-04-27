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
        Schema::create('stocks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('item_id')->constrained('items');
    $table->foreignId('rack_id')->constrained('racks');
    $table->decimal('qty', 10, 2)->default(0);
    $table->decimal('reserved_qty', 10, 2)->default(0);
    $table->string('batch_no', 100)->nullable();
    $table->date('expiry_date')->nullable();
    $table->timestamps();
    $table->unique(['item_id', 'rack_id', 'batch_no']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
