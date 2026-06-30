<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->integer('stock_depot')->default(0);
            $table->integer('stock_boutique')->default(0);
            $table->integer('alert_threshold_depot')->default(5);
            $table->integer('alert_threshold_boutique')->default(3);
            $table->decimal('purchase_price', 8, 2)->default(0);
            $table->decimal('sale_price', 8, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
