<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kuota cuti kini per jenis, bukan hanya satu saldo tahunan. Setiap baris
     * mewakili saldo satu jenis cuti pada satu tahun; kunci unik menyesuaikan
     * agar tiap jenis punya barisnya sendiri.
     */
    public function up(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->string('type')->default('tahunan')->after('year');
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'year']);
            $table->unique(['employee_id', 'year', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'year', 'type']);
            $table->unique(['employee_id', 'year']);
            $table->dropColumn('type');
        });
    }
};
