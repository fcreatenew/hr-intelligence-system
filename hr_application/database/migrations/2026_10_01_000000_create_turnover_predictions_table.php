<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnover_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();
            $table->decimal('probabilitas_keluar', 5, 4);
            $table->boolean('prediksi_keluar');
            $table->string('level_risiko', 20);
            $table->string('model_version')->nullable();
            $table->decimal('threshold', 4, 2)->nullable();
            $table->timestamp('predicted_at');
            $table->timestamps();

            $table->index(['employee_id', 'predicted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnover_predictions');
    }
};
