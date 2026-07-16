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
            $table->foreignId('medicine_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained();
            $table->string('batch_number');
            $table->date('expiry_date')->index();
            $table->integer('quantity');
            $table->decimal('selling_price', 10, 2);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->integer('min_stock')->default(10);
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
