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
        // First create the table without foreign key constraints
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('ticket_id');
            $table->text('description');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index(['invoice_id']);
            $table->index(['ticket_id']);
        });

        // Add foreign key constraints after table creation if tables exist
        if (Schema::hasTable('invoices') && Schema::hasTable('tickets')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
                $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};