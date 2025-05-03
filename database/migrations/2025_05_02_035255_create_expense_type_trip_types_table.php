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
        Schema::create('expense_type_trip_types', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('trip_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('trip_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('default_charge', 10, 2)->default(0);
            $table->boolean('is_master')->default(false);
            $table->timestamps();

            // Add indexes for better performance
            $table->index('trip_type_id', 'idx_ettt_trip_type');
            $table->index('expense_type_id', 'idx_ettt_expense_type');
            $table->index('trip_id', 'idx_ettt_trip');
            $table->index('is_master', 'idx_ettt_master');
            
            // Add a unique constraint to prevent duplicate relationships
            $table->unique(['trip_type_id', 'expense_type_id', 'trip_id'], 'unique_expense_trip_relation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_type_trip_types');
    }
};
