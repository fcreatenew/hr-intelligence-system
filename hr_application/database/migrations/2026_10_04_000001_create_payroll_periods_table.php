<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Periode payroll: satu baris mewakili satu bulan penggajian (YYYY-MM).
 *
 * Periode menjadi wadah hasil perhitungan sehingga angka payroll tidak lagi
 * ditulis langsung ke tabel employees — komponen gaji karyawan hanya menjadi
 * *input*, sedangkan hasil final disimpan per periode agar bisa diaudit dan
 * dihitung ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7)->unique();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('employee_count')->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
