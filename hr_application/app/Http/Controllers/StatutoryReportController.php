<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStatutoryReportRequest;
use App\Models\StatutoryReport;
use App\Services\StatutoryReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StatutoryReportController extends Controller
{
    public function __construct(private readonly StatutoryReportService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', StatutoryReport::class);

        return view('compliance.index', [
            'reports' => StatutoryReport::query()->with('generator')->orderByDesc('period')->paginate(20),
            'types' => StatutoryReport::TYPES,
        ]);
    }

    public function store(GenerateStatutoryReportRequest $request): RedirectResponse
    {
        $report = $this->service->generate(
            $request->string('type')->toString(),
            $request->string('period')->toString(),
            $request->user()->id,
        );

        return redirect()
            ->route('statutory-reports.show', $report)
            ->with('status', 'Laporan dibuat (estimasi).');
    }

    public function show(StatutoryReport $statutoryReport): View
    {
        $this->authorize('view', $statutoryReport);

        return view('compliance.show', [
            'report' => $statutoryReport->load('generator'),
        ]);
    }

    /** Kunci laporan sebagai final agar tidak dihitung ulang tanpa sengaja. */
    public function finalize(StatutoryReport $statutoryReport): RedirectResponse
    {
        $this->authorize('update', $statutoryReport);

        $statutoryReport->update(['status' => StatutoryReport::STATUS_FINAL]);

        return redirect()
            ->route('statutory-reports.show', $statutoryReport)
            ->with('status', 'Laporan ditandai final.');
    }

    public function destroy(StatutoryReport $statutoryReport): RedirectResponse
    {
        $this->authorize('delete', $statutoryReport);

        $statutoryReport->delete();

        return redirect()
            ->route('statutory-reports.index')
            ->with('status', 'Laporan dihapus.');
    }
}
