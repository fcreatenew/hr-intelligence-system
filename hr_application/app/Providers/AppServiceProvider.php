<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Candidate;
use App\Models\CompensationComponent;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\JobOpening;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PerformanceGoal;
use App\Models\PerformanceReview;
use App\Models\StatutoryReport;
use App\Models\User;
use App\Policies\AttendancePolicy;
use App\Policies\CandidatePolicy;
use App\Policies\CompensationComponentPolicy;
use App\Policies\EmployeeDocumentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\JobOpeningPolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\PayrollPeriodPolicy;
use App\Policies\PerformanceGoalPolicy;
use App\Policies\PerformanceReviewPolicy;
use App\Policies\StatutoryReportPolicy;
use App\Policies\UserPolicy;
use App\Services\DatabaseBackupService;
use App\Services\PayrollCalculationService;
use App\Services\PayrollIntelligenceService;
use App\Services\StatutoryReportService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->app->singleton(DatabaseBackupService::class, function () {
            return new DatabaseBackupService(
                disk: (string) config('backup.disk', 'local'),
                directory: (string) config('backup.directory', 'backups'),
                keep: (int) config('backup.keep', 14),
                timeout: (int) config('backup.timeout', 300),
            );
        });

        $this->app->singleton(StatutoryReportService::class, function () {
            return new StatutoryReportService(
                bpjsKesehatanEmployeeRate: (float) config('statutory.bpjs_kesehatan_employee_rate', 0.01),
                bpjsKesehatanEmployerRate: (float) config('statutory.bpjs_kesehatan_employer_rate', 0.04),
                bpjsJhtEmployeeRate: (float) config('statutory.bpjs_jht_employee_rate', 0.02),
                bpjsJhtEmployerRate: (float) config('statutory.bpjs_jht_employer_rate', 0.037),
                pph21Rate: (float) config('statutory.pph21_rate', 0.05),
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
        Gate::policy(JobOpening::class, JobOpeningPolicy::class);
        Gate::policy(Candidate::class, CandidatePolicy::class);
        Gate::policy(PerformanceGoal::class, PerformanceGoalPolicy::class);
        Gate::policy(PerformanceReview::class, PerformanceReviewPolicy::class);
        Gate::policy(CompensationComponent::class, CompensationComponentPolicy::class);
        Gate::policy(StatutoryReport::class, StatutoryReportPolicy::class);
        Gate::policy(EmployeeDocument::class, EmployeeDocumentPolicy::class);

        Gate::define('manage-hr-intelligence', function (User $user) {
            return in_array($user->role, ['admin', 'hr-manager']);
        });

        $this->configureRateLimiting();
    }

    /**
     * Batas laju untuk mencegah brute force login dan penyalahgunaan endpoint.
     *
     * Limit login memakai kombinasi email + IP agar percobaan terhadap satu akun
     * dari banyak IP tetap terhitung, sekaligus tidak memblokir IP bersama.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('admin-api', fn (Request $request) => Limit::perMinute(30)->by(
            $request->user()?->getAuthIdentifier() ?? $request->ip(),
        ));
    }
}
