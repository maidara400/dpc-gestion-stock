<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Seulement pour les entrées dépôt (depot_entry)
            $table->decimal('amount_due', 10, 2)->nullable()->after('notes');
            $table->date('payment_due_date')->nullable()->after('amount_due');
            $table->enum('payment_status', ['paid', 'pending', 'overdue'])
                  ->nullable()->after('payment_due_date');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->foreignId('supplier_id')->nullable()->after('user_id')
                  ->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Supplier::class);
            $table->dropColumn(['amount_due', 'payment_due_date', 'payment_status', 'paid_at', 'supplier_id']);
        });
    }
};
