<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class LeaveRequestController extends Controller
{
    public function __construct(private LeaveService $leaveService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $leaveRequests = LeaveRequest::query()
            ->with(['employee', 'decidedBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('leave-requests.index', [
            'leaveRequests' => $leaveRequests,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
            'statuses' => [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_REJECTED],
            'filters' => [
                'status' => $request->string('status')->toString(),
                'employee_id' => $request->string('employee_id')->toString(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', LeaveRequest::class);

        $employeeId = $request->integer('employee_id') ?: null;

        return view('leave-requests.create', [
            'leaveRequest' => new LeaveRequest(['type' => 'tahunan', 'employee_id' => $employeeId]),
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
            'types' => LeaveRequest::TYPES,
            'balances' => $employeeId ? Employee::find($employeeId)?->leaveBalancesFor() : [],
        ]);
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['days'] = LeaveRequest::countDays($data['start_date'], $data['end_date']);
        $data['status'] = LeaveRequest::STATUS_PENDING;

        $leaveRequest = LeaveRequest::create($data);

        return redirect()
            ->route('leave-requests.show', $leaveRequest)
            ->with('status', 'Pengajuan cuti dibuat dan menunggu keputusan.');
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $this->authorize('view', $leaveRequest);

        return view('leave-requests.show', [
            'leaveRequest' => $leaveRequest->load(['employee', 'decidedBy']),
            'balance' => $leaveRequest->employee->leaveBalanceFor(
                (int) $leaveRequest->start_date->year,
                $leaveRequest->type,
            ),
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('decide', $leaveRequest);

        $note = $request->string('decision_note')->toString() ?: null;

        try {
            $this->leaveService->approve($leaveRequest, $request->user(), $note);
        } catch (RuntimeException $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()
            ->route('leave-requests.show', $leaveRequest)
            ->with('status', 'Cuti disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('decide', $leaveRequest);

        $note = $request->string('decision_note')->toString() ?: null;

        try {
            $this->leaveService->reject($leaveRequest, $request->user(), $note);
        } catch (RuntimeException $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()
            ->route('leave-requests.show', $leaveRequest)
            ->with('status', 'Pengajuan cuti ditolak.');
    }

    public function destroy(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('delete', $leaveRequest);

        $leaveRequest->delete();

        return redirect()
            ->route('leave-requests.index')
            ->with('status', 'Pengajuan cuti dihapus.');
    }

    /**
     * Ringkasan saldo cuti semua karyawan untuk tahun berjalan.
     */
    public function balances(Request $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $year = $request->integer('year') ?: (int) now()->year;

        $rows = Employee::query()
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($year) {
                return [
                    'employee' => $employee,
                    'balances' => $employee->leaveBalancesFor($year),
                    'pending_days' => (int) LeaveRequest::query()
                        ->where('employee_id', $employee->id)
                        ->pending()
                        ->whereYear('start_date', $year)
                        ->sum('days'),
                ];
            });

        return view('leave-requests.balances', [
            'rows' => $rows,
            'year' => $year,
            'types' => LeaveRequest::TYPES,
        ]);
    }
}
