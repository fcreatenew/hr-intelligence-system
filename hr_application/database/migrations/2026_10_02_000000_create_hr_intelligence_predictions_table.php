<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_intelligence_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('use_case', 40)->index();
            $table->string('employee_id')->index();
            $table->float('skor')->nullable();
            $table->string('label', 40)->nullable();
            $table->boolean('is_flag')->default(false);
            $table->json('detail')->nullable();
            $table->string('model_version')->nullable();
            $table->timestamp('predicted_at');
            $table->timestamps();

            $table->index(['use_case', 'employee_id', 'predicted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_intelligence_predictions');
    }
};
