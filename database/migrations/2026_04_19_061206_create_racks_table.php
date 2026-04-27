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
       Schema::create('racks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
    $table->string('code', 20);
    $table->string('full_code', 50)->nullable(); // e.g. JKT-STG-R01
    $table->integer('total_levels')->default(4);
    $table->decimal('max_weight_kg', 10, 2)->nullable();
    $table->enum('status', ['available', 'occupied', 'reserved', 'blocked'])->default('available');
    $table->timestamps();
    $table->unique(['zone_id', 'code']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racks');
    }
};
