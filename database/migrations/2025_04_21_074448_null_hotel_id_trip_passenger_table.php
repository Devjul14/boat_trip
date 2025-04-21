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
        Schema::table('trip_passengers', function (Blueprint $table) {
            // Modify hotel_id to be nullable
            $table->foreignId('hotel_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_passengers', function (Blueprint $table) {
            // Change back to non-nullable
            $table->foreignId('hotel_id')->nullable(false)->change();
        });
    }
};