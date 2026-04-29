<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Buat tabel drivers ───────────────────────────────────────────────
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('license_number')->nullable();
            $table->string('license_type')->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('availability_status', ['available', 'on_trip', 'off_duty', 'rest'])->default('off_duty');
            $table->decimal('current_lat', 10, 7)->nullable();
            $table->decimal('current_lng', 10, 7)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};