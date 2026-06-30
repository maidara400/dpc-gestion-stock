<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['depot_entry', 'transfer', 'sale', 'adjustment']);
            $table->integer('quantity');
            $table->string('location_from')->nullable();
            $table->string('location_to')->nullable();
            $table->text('notes')->nullable();
            $table->integer('stock_depot_after');
            $table->integer('stock_boutique_after');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
