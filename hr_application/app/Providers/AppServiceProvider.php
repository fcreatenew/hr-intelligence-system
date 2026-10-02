<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Policies\AttendancePolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LeaveRequestPolicy;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);

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
