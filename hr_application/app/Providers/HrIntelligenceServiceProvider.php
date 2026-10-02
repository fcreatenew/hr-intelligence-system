<?php

namespace App\Providers;

use App\Contracts\EmployeeFeatureMapper;
use App\Services\HrIntelligenceService;
use App\Services\Mappers\DefaultEmployeeFeatureMapper;
use Illuminate\Support\ServiceProvider;

class HrIntelligenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/hr_intelligence.php', 'hr_intelligence');

        $this->app->singleton(HrIntelligenceService::class, function () {
            $config = config('hr_intelligence');

            if (empty($config['api_key'])) {
                throw new \RuntimeException(
                    'HR_INTELLIGENCE_API_KEY belum diset. Isi di file .env aplikasi ini.'
                );
            }

            return new HrIntelligenceService(
                baseUrl: rtrim($config['base_url'], '/'),
                apiKey: $config['api_key'],
                timeoutSeconds: $config['timeout'],
                retryTimes: $config['retry_times'],
                retryDelayMs: $config['retry_delay_ms'],
                batchChunkSize: $config['batch_chunk_size'],
                useCases: $config['use_cases'] ?? [],
            );
        });

        $this->app->bind(EmployeeFeatureMapper::class, DefaultEmployeeFeatureMapper::class);

        $this->app->alias(HrIntelligenceService::class, 'hr-intelligence');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\PredictTurnoverCommand::class,
                \App\Console\Commands\PredictUseCaseCommand::class,
            ]);

            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }
    }
}
