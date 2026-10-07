<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compensation_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('tunjangan');
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('recurring')->default(true);
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'type']);
        });

        Schema::create('statutory_reports', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('period', 10);
            $table->string('status', 20)->default('draft');
            $table->json('payload')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_reports');
        Schema::dropIfExists('compensation_components');
    }
};
