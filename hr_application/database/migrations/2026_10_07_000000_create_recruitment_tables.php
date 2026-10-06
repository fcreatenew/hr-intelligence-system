<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department', 80);
            $table->string('employment_type', 30)->default('tetap');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->unsignedInteger('quota')->default(1);
            $table->string('status', 20)->default('draft');
            $table->date('opened_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'department']);
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_opening_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('source', 40)->nullable();
            $table->string('stage', 20)->default('melamar');
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->date('applied_at')->nullable();
            $table->timestamps();

            $table->index(['job_opening_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('job_openings');
    }
};
