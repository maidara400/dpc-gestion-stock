<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('ticket_number')->nullable()->after('notes');
            $table->enum('payment_method', ['wave', 'om', 'cash'])->nullable()->after('ticket_number');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['ticket_number', 'payment_method']);
        });
    }
};
