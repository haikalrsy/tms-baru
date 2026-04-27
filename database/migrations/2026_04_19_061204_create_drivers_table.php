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
       Schema::create('drivers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('license_number', 50)->nullable();
    $table->enum('license_type', ['SIM A', 'SIM B1', 'SIM B2'])->nullable();
    $table->date('license_expiry')->nullable();
    $table->string('phone', 20)->nullable();
    $table->text('address')->nullable();
    $table->enum('availability_status', ['available', 'on_trip', 'off_duty', 'rest'])->default('available');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
