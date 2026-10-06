<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category', 60)->default('lainnya');
            $table->string('provider')->nullable();
            $table->string('mode', 20)->default('online');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('duration_hours')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->unsignedInteger('quota')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['status', 'start_date']);
        });

        Schema::create('training_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('terdaftar');
            $table->unsignedTinyInteger('score')->nullable();
            $table->boolean('certified')->default(false);
            $table->string('certificate_number')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Satu karyawan hanya boleh terdaftar sekali per pelatihan.
            $table->unique(['training_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_participants');
        Schema::dropIfExists('trainings');
    }
};
