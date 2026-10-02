<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveCalendarController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PayrollSlipController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/hr_intelligence.php';

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('employees/{employee}/terminate', [EmployeeController::class, 'terminate'])->name('employees.terminate');
    Route::resource('employees', EmployeeController::class);

    Route::get('attendances/recap', [AttendanceController::class, 'recap'])->name('attendances.recap');
    Route::get('attendances/import/template', [AttendanceController::class, 'importTemplate'])->name('attendances.import-template');
    Route::post('attendances/import', [AttendanceController::class, 'import'])->name('attendances.import');
    Route::resource('attendances', AttendanceController::class)->except(['show']);

    Route::get('leave-requests/balances', [LeaveRequestController::class, 'balances'])->name('leave-requests.balances');
    Route::get('leave-requests/calendar', [LeaveCalendarController::class, 'index'])->name('leave-requests.calendar');
    Route::post('leave-requests/{leave_request}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('leave-requests/{leave_request}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::resource('leave-requests', LeaveRequestController::class);

    Route::post('payroll-periods/{payroll_period}/calculate', [PayrollPeriodController::class, 'calculate'])->name('payroll-periods.calculate');
    Route::post('payroll-periods/{payroll_period}/finalize', [PayrollPeriodController::class, 'finalize'])->name('payroll-periods.finalize');
    Route::get('payroll-periods/{payroll_period}/export', [PayrollPeriodController::class, 'export'])->name('payroll-periods.export');
    Route::get('payroll-periods/{payroll_period}/items/{item}/slip', [PayrollSlipController::class, 'show'])->name('payroll-periods.slip');
    Route::resource('payroll-periods', PayrollPeriodController::class)->only(['index', 'store', 'show', 'destroy']);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('admin/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::resource('users', UserController::class)->except(['show']);
});
