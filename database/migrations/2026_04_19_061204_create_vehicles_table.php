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
       
Schema::create('vehicles', function (Blueprint $table) {
    $table->id();
    $table->string('plate_number', 20)->unique();
    $table->string('vehicle_type', 50); // motor, pickup, truk, box
    $table->string('brand', 100)->nullable();
    $table->string('model', 100)->nullable();
    $table->year('year')->nullable();
    $table->decimal('max_weight_kg', 10, 2)->nullable();
    $table->decimal('max_volume_m3', 10, 2)->nullable();
    $table->enum('status', ['available', 'on_trip', 'maintenance', 'inactive'])->default('available');
    $table->date('stnk_expired_at')->nullable();
    $table->date('kir_expired_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
