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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('bill_number');
            $table->foreignId('trip_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('boat_id')->constrained()->onDelete('cascade');
            $table->foreignId('boatman_id')->constrained('users')->onDelete('cascade');
            $table->text('remarks')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->decimal('petrol_consumed', 10, 2)->default(0);
            $table->decimal('petrol_filled', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
