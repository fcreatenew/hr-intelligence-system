<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LeaveCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $month = $this->resolveMonth($request->string('month')->toString());

        $start = $month->startOfMonth();
        $end = $month->endOfMonth();

        $requests = LeaveRequest::query()
            ->approved()
            ->with('employee:id,name,nik,department')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->orderBy('start_date')
            ->get();

        $days = $this->buildDays($start, $end, $requests);

        return view('leave-requests.calendar', [
            'month' => $month,
            'previousMonth' => $month->subMonth()->format('Y-m'),
            'nextMonth' => $month->addMonth()->format('Y-m'),
            'weekdays' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            'days' => $days,
            'employeeCount' => $requests->pluck('employee_id')->unique()->count(),
            'leaveDayCount' => $requests->sum('days'),
        ]);
    }

    private function resolveMonth(string $value): CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return CarbonImmutable::createFromFormat('Y-m', $value)->startOfMonth();
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    /**
     * Bangun grid kalender (selalu dimulai Senin) beserta cuti yang beririsan
     * dengan tiap hari, agar tumpang tindih cuti terlihat langsung.
     *
     * @param  Collection<int, LeaveRequest>  $requests
     * @return array<int, array{date: CarbonImmutable, inMonth: bool, isToday: bool, leaves: array<int, array<string, mixed>>}>
     */
    private function buildDays(CarbonImmutable $start, CarbonImmutable $end, $requests): array
    {
        $cursor = $start->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $end->endOfWeek(CarbonImmutable::SUNDAY);
        $today = CarbonImmutable::now()->startOfDay();

        $days = [];

        while ($cursor->lessThanOrEqualTo($gridEnd)) {
            $leaves = [];

            foreach ($requests as $leave) {
                if ($leave->start_date->lessThanOrEqualTo($cursor) && $leave->end_date->greaterThanOrEqualTo($cursor)) {
                    $leaves[] = [
                        'id' => $leave->id,
                        'name' => $leave->employee?->name ?? '—',
                        'type' => $leave->type,
                        'department' => $leave->employee?->department,
                    ];
                }
            }

            $days[] = [
                'date' => $cursor,
                'inMonth' => $cursor->month === $start->month && $cursor->year === $start->year,
                'isToday' => $cursor->equalTo($today),
                'leaves' => $leaves,
            ];

            $cursor = $cursor->addDay();
        }

        return $days;
    }
}
