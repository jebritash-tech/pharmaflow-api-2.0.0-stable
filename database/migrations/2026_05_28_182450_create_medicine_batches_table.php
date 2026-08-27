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
        Schema::create('medicine_batches', function (Blueprint $table) {
           $table->id();
            $table->foreignId('medicine_id')->constrained('medicines')->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained('purchase_items')->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreignId('purchase_unit_id')
            ->constrained('medicine_units');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('buy_price', 12, 2);
            $table->decimal('quantity', 12, 2);
            $table->decimal('remaining_quantity', 12, 2);
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_batches');
    }
};
