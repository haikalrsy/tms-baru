<?php
// ============================================================
// database/migrations/2024_02_01_000000_create_delivery_notes_table.php
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('dn_number', 50)->unique();          // DN-202501-00001
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('created_by')->constrained('users');

            // Dokumen info
            $table->date('delivery_date');
            $table->string('shipper_name', 200)->nullable();     // pengirim
            $table->string('shipper_address')->nullable();
            $table->string('receiver_name', 200)->nullable();    // penerima
            $table->string('receiver_address')->nullable();
            $table->string('receiver_phone', 20)->nullable();

            // Kendaraan & driver snapshot
            $table->string('vehicle_plate', 20)->nullable();
            $table->string('vehicle_type', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 20)->nullable();

            // Muatan
            $table->integer('total_packages')->default(0);
            $table->decimal('total_weight_kg', 10, 2)->default(0);
            $table->decimal('total_volume_m3', 10, 3)->default(0);
            $table->string('cargo_description')->nullable();

            // Status & dokumen
            $table->enum('status', ['draft', 'issued', 'delivered', 'returned'])->default('draft');
            $table->string('pdf_path')->nullable();              // path ke PDF yang digenerate
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['delivery_order_id']);
            $table->index(['customer_id', 'delivery_date']);
        });

        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('item_name', 200);                   // snapshot nama item
            $table->string('item_sku', 50)->nullable();
            $table->string('uom', 20)->default('pcs');
            $table->decimal('qty', 10, 2);
            $table->decimal('weight_kg', 10, 3)->nullable();
            $table->string('package_type', 50)->nullable();     // karton, palet, dll
            $table->string('batch_no', 100)->nullable();
            $table->integer('box_count')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');
    }
};