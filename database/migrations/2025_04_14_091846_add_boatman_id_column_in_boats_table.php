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
        // Langkah 1: Hapus foreign key constraint jika ada
        Schema::table('boats', function (Blueprint $table) {
            if (Schema::hasColumn('boats', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('boats', 'boatman_id')) {
                $table->dropForeign(['boatman_id']);
                $table->dropColumn('boatman_id');
            }
        });
        
        // Langkah 2: Tambahkan kembali kolom user_id dan boatman_id
        Schema::table('boats', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('registration_number')->constrained('users')->nullOnDelete();
            $table->foreignId('boatman_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boats', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropForeign(['boatman_id']);
            $table->dropColumn('boatman_id');
        });
        
        // Kembalikan kolom user_id seperti sebelumnya jika perlu
        Schema::table('boats', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('registration_number')->constrained('users')->nullOnDelete();
        });
    }
};