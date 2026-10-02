<?php

namespace App\Facades;

use App\Services\HrIntelligenceService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array health()
 * @method static array modelInfo()
 * @method static array predict(array $employee)
 * @method static array predictBatch(array $employees)
 * @method static int maxBatchSize()
 * @method static array useCases()
 * @method static array predictUseCase(string $useCase, array $employee)
 * @method static array predictUseCaseBatch(string $useCase, array $employees)
 * @method static array useCaseInfo(string $useCase)
 *
 * @see HrIntelligenceService
 */
class HrIntelligence extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'hr-intelligence';
    }
}
