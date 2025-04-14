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
    // Langkah 1: Simpan data yang ada
    $passengers = DB::table('trip_passengers')->select('id', 'payment_status')->get();
    
    // Langkah 2: Hapus kolom enum
    Schema::table('trip_passengers', function (Blueprint $table) {
        $table->dropColumn('payment_status');
    });
    
    // Langkah 3: Tambahkan kolom baru sebagai string
    Schema::table('trip_passengers', function (Blueprint $table) {
        $table->string('payment_status')->default('pending');
    });
    
    // Langkah 4: Pulihkan data yang disimpan sebelumnya
    foreach ($passengers as $passenger) {
        DB::table('trip_passengers')
            ->where('id', $passenger->id)
            ->update(['payment_status' => $passenger->payment_status]);
    }
}

/**
 * Reverse the migrations.
 */
public function down(): void
{
    // Simpan data yang ada
    $passengers = DB::table('trip_passengers')->select('id', 'payment_status')->get();
    
    // Hapus kolom string
    Schema::table('trip_passengers', function (Blueprint $table) {
        $table->dropColumn('payment_status');
    });
    
    // Tambahkan kembali kolom dengan tipe enum
    Schema::table('trip_passengers', function (Blueprint $table) {
        $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
    });
    
    // Pulihkan data
    foreach ($passengers as $passenger) {
        // Pastikan nilai masih valid untuk enum
        $status = in_array($passenger->payment_status, ['pending', 'paid', 'refunded']) 
            ? $passenger->payment_status 
            : 'pending';
            
        DB::table('trip_passengers')
            ->where('id', $passenger->id)
            ->update(['payment_status' => $status]);
    }
}
};
