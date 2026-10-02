<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah atribut yang dibutuhkan model HR Intelligence ke tabel employees.
 * Kolom dibuat nullable agar data lama tetap valid; mapper mengisi default
 * bila nilainya kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Inti (dipakai hampir semua use case)
            $table->date('tanggal_lahir')->nullable()->after('join_date');
            $table->date('tanggal_promosi')->nullable()->after('tanggal_lahir');
            $table->string('status_kerja', 20)->nullable()->after('status');
            $table->string('pendidikan', 20)->nullable()->after('status_kerja');
            $table->unsignedBigInteger('gaji_bulanan')->nullable()->after('pendidikan');
            $table->unsignedInteger('jam_lembur_bulanan')->nullable()->after('gaji_bulanan');

            // Absensi
            $table->decimal('jarak_km', 6, 1)->nullable();
            $table->decimal('beban_kerja', 6, 1)->nullable();
            $table->decimal('kepuasan_kerja', 3, 2)->nullable();
            $table->unsignedTinyInteger('riwayat_absen_90hari')->nullable();

            // Kinerja
            $table->unsignedTinyInteger('jumlah_training_setahun')->nullable();
            $table->unsignedSmallInteger('proyek_selesai')->nullable();
            $table->decimal('skor_umpan_balik_360', 3, 2)->nullable();
            $table->unsignedSmallInteger('hari_absen_setahun')->nullable();

            // Survival
            $table->decimal('bulan_sejak_promosi', 6, 1)->nullable();

            // Payroll
            $table->string('periode', 7)->nullable();
            $table->string('level', 20)->nullable();
            $table->unsignedBigInteger('gaji_pokok')->nullable();
            $table->decimal('tunjangan', 14, 2)->nullable();
            $table->unsignedInteger('lembur_jam')->nullable();
            $table->decimal('tarif_lembur', 14, 2)->nullable();
            $table->decimal('potongan', 14, 2)->nullable();
            $table->decimal('total_gaji', 16, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_lahir',
                'tanggal_promosi',
                'status_kerja',
                'pendidikan',
                'gaji_bulanan',
                'jam_lembur_bulanan',
                'jarak_km',
                'beban_kerja',
                'kepuasan_kerja',
                'riwayat_absen_90hari',
                'jumlah_training_setahun',
                'proyek_selesai',
                'skor_umpan_balik_360',
                'hari_absen_setahun',
                'bulan_sejak_promosi',
                'periode',
                'level',
                'gaji_pokok',
                'tunjangan',
                'lembur_jam',
                'tarif_lembur',
                'potongan',
                'total_gaji',
            ]);
        });
    }
};
