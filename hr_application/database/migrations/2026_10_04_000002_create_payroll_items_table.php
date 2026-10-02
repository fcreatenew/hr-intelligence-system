<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baris payroll per karyawan per periode.
 *
 * `lembur_jam` dan `potongan` disimpan hasil perhitungan server (bukan input
 * manual), sehingga dapat ditelusuri asalnya. `skor_anomali`/`level` diisi
 * dari hasil Payroll Intelligence bila tersedia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('periode', 7);
            $table->string('departemen')->nullable();
            $table->string('level', 20)->nullable();

            $table->unsignedBigInteger('gaji_pokok')->default(0);
            $table->decimal('tunjangan', 14, 2)->default(0);
            $table->unsignedInteger('lembur_jam')->default(0);
            $table->decimal('tarif_lembur', 14, 2)->default(0);
            $table->decimal('biaya_lembur', 14, 2)->default(0);
            $table->decimal('potongan', 14, 2)->default(0);
            $table->decimal('total_gaji', 16, 2)->default(0);

            $table->decimal('skor_anomali', 5, 4)->nullable();
            $table->string('level_anomali', 20)->nullable();
            $table->text('alasan_anomali')->nullable();

            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
            $table->index('periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
