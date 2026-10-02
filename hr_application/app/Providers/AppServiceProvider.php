<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Policies\AttendancePolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\PayrollPeriodPolicy;
use App\Policies\UserPolicy;
use App\Services\PayrollCalculationService;
use App\Services\PayrollIntelligenceService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PayrollCalculationService::class, function () {
            return new PayrollCalculationService(
                defaultOvertimeRate: (float) config('payroll.default_overtime_rate', 0),
                workingDaysPerMonth: (int) config('payroll.working_days_per_month', 22),
            );
        });

        $this->app->singleton(PayrollIntelligenceService::class, function () {
            $baseUrl = (string) config('payroll.base_url', '');

            return new PayrollIntelligenceService(
                baseUrl: $baseUrl,
                timeoutSeconds: (int) config('payroll.timeout', 10),
                enabled: $baseUrl !== '',
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);
        Gate::policy(PayrollPeriod::class, PayrollPeriodPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('manage-hr-intelligence', function (User $user) {
            return in_array($user->role, ['admin', 'hr-manager']);
        });
    }
}
