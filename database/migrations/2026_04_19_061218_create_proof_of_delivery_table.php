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
        Schema::create('proof_of_delivery', function (Blueprint $table) {
    $table->id();
    $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
    $table->foreignId('submitted_by')->constrained('users');
    $table->string('recipient_name', 100);
    $table->string('recipient_title', 50)->nullable();
    $table->string('photo_path')->nullable();
    $table->string('signature_path')->nullable();
    $table->text('notes')->nullable();
    $table->enum('status', ['submitted', 'verified', 'rejected'])->default('submitted');
    $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('verified_at')->nullable();
    $table->timestamp('submitted_at')->useCurrent();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proof_of_delivery');
    }
};
