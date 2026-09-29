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
