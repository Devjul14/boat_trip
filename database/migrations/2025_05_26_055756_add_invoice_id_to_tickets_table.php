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
        Schema::table('tickets', function (Blueprint $table) {
            // Add the invoice_id column
            $table->unsignedBigInteger('invoice_id')->nullable()->after('id');
            $table->index(['invoice_id']);
        });

        // Add foreign key constraint if invoices table exists
        if (Schema::hasTable('invoices')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Drop foreign key first if it exists
            if (Schema::hasColumn('tickets', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
        });
    }
};