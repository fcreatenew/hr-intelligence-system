<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayrollPeriodRequest;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollPeriodController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PayrollPeriod::class);

        $periods = PayrollPeriod::query()
            ->withCount('items')
            ->orderByDesc('periode')
            ->paginate(15)
            ->withQueryString();

        return view('payroll-periods.index', [
            'periods' => $periods,
            'defaultPeriode' => now()->format('Y-m'),
        ]);
    }

    public function store(StorePayrollPeriodRequest $request): RedirectResponse
    {
        try {
            $period = $this->payrollService->openPeriod($request->string('periode')->toString());
        } catch (RuntimeException $e) {
            return back()->withErrors(['periode' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('payroll-periods.show', $period)
            ->with('status', 'Periode payroll dibuka.');
    }

    public function show(PayrollPeriod $payrollPeriod): View
    {
        $this->authorize('view', $payrollPeriod);

        $items = $payrollPeriod->items()
            ->with('employee')
            ->orderByDesc('skor_anomali')
            ->orderBy('id')
            ->paginate(25);

        return view('payroll-periods.show', [
            'period' => $payrollPeriod,
            'items' => $items,
            'summary' => $this->payrollService->summary($payrollPeriod),
        ]);
    }

    public function calculate(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('calculate', $payrollPeriod);

        try {
            $this->payrollService->calculate($payrollPeriod, request()->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['payroll' => $e->getMessage()]);
        }

        return redirect()
            ->route('payroll-periods.show', $payrollPeriod)
            ->with('status', 'Payroll periode '.$payrollPeriod->periode.' berhasil dihitung.');
    }

    public function finalize(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('finalize', $payrollPeriod);

        try {
            $this->payrollService->finalize($payrollPeriod, request()->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['payroll' => $e->getMessage()]);
        }

        return redirect()
            ->route('payroll-periods.show', $payrollPeriod)
            ->with('status', 'Periode '.$payrollPeriod->periode.' difinalisasi.');
    }

    public function destroy(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('delete', $payrollPeriod);

        $payrollPeriod->delete();

        return redirect()
            ->route('payroll-periods.index')
            ->with('status', 'Periode payroll dihapus.');
    }

    /** Ekspor slip/komponen payroll periode ke CSV. */
    public function export(PayrollPeriod $payrollPeriod): StreamedResponse
    {
        $this->authorize('view', $payrollPeriod);

        $items = $payrollPeriod->items()->with('employee')->orderBy('id')->get();
        $filename = 'payroll-'.$payrollPeriod->periode.'.csv';

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'nik', 'nama', 'departemen', 'level', 'gaji_pokok', 'tunjangan',
                'lembur_jam', 'tarif_lembur', 'biaya_lembur', 'potongan',
                'total_gaji', 'level_anomali',
            ]);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->employee->nik,
                    $item->employee->name,
                    $item->departemen,
                    $item->level,
                    $item->gaji_pokok,
                    $item->tunjangan,
                    $item->lembur_jam,
                    $item->tarif_lembur,
                    $item->biaya_lembur,
                    $item->potongan,
                    $item->total_gaji,
                    $item->level_anomali,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
