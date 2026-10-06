<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('period', 10);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('weight')->default(0);
            $table->decimal('target', 12, 2)->nullable();
            $table->decimal('achievement', 12, 2)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index(['employee_id', 'period']);
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('period', 10);
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_date')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('kategori', 20)->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            // Satu karyawan punya satu review per periode.
            $table->unique(['employee_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('performance_goals');
    }
};
