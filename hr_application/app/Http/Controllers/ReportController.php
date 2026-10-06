<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Support\Pdf\SimplePdfWriter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $report = $this->report($request);

        return view('reports.index', [
            'departments' => $report['departments'],
            'totals' => $report['totals'],
            'filters' => ['from' => $report['from'], 'to' => $report['to']],
        ]);
    }

    /**
     * Ekspor rekap per departemen sebagai CSV.
     *
     * Memakai `streamDownload` agar tetap hemat memori walau jumlah karyawan
     * bertambah banyak; format CSV dipilih supaya bisa langsung dibuka di
     * Excel/Sheets tanpa dependensi tambahan.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        $report = $this->report($request);
        $filename = 'laporan-hr-'.$report['from'].'-'.$report['to'].'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar

            fputcsv($out, ['Departemen', 'Karyawan', 'Aktif', 'Non-aktif', 'Keluar (periode)', 'Rasio turnover (%)', 'Total gaji bulanan', 'Rata-rata tidak hadir']);

            foreach ($report['departments'] as $row) {
                fputcsv($out, [
                    $row['department'],
                    $row['total'],
                    $row['aktif'],
                    $row['non_aktif'],
                    $row['keluar'],
                    $row['turnover_rate'],
                    $row['total_gaji'],
                    $row['rata_tidak_hadir'],
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Total', $report['totals']['total'], $report['totals']['aktif'], $report['totals']['non_aktif'], $report['totals']['keluar'], $report['totals']['turnover_rate'], $report['totals']['total_gaji'], '']);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Ekspor rekap per departemen sebagai PDF.
     *
     * Memakai penulis PDF minimalis (`App\Support\Pdf\SimplePdfWriter`) agar
     * tidak menambah dependensi composer. Halaman kedua dan seterusnya dibuat
     * otomatis bila baris melebihi tinggi halaman.
     */
    public function exportPdf(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $report = $this->report($request);
        $filename = 'laporan-hr-'.$report['from'].'-'.$report['to'].'.pdf';

        return response($this->renderPdf($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function renderPdf(array $report): string
    {
        $pdf = new SimplePdfWriter;
        $margin = 40.0;
        $y = $pdf->height - 50;
        $bottom = 60.0;

        $headers = [
            ['Departemen', 0.0],
            ['Total', 150.0],
            ['Aktif', 190.0],
            ['Keluar', 230.0],
            ['Turnover %', 275.0],
            ['Total gaji', 340.0],
            ['Avg absen', 440.0],
        ];

        $pdf->text('Laporan HR — Headcount, Turnover & Biaya', $margin, $y, 14);
        $y -= 18;
        $pdf->text('Periode '.$report['from'].' s/d '.$report['to'], $margin, $y, 9);
        $y -= 24;

        $drawHeaders = function () use ($pdf, $margin, &$y, $headers) {
            foreach ($headers as [$label, $dx]) {
                $pdf->text($label, $margin + $dx, $y, 9);
            }
            $pdf->line($margin, $y - 4, $pdf->width - $margin, $y - 4);
            $y -= 16;
        };

        $drawHeaders();

        foreach ($report['departments'] as $row) {
            if ($y < $bottom) {
                $pdf->newPage();
                $y = $pdf->height - 50;
                $drawHeaders();
            }

            $cells = [
                (string) $row['department'],
                (string) $row['total'],
                (string) $row['aktif'],
                (string) $row['keluar'],
                number_format((float) $row['turnover_rate'], 1, ',', '.'),
                number_format((float) $row['total_gaji'], 0, ',', '.'),
                number_format((float) $row['rata_tidak_hadir'], 1, ',', '.'),
            ];

            foreach ($headers as $i => [$label, $dx]) {
                $pdf->text($cells[$i], $margin + $dx, $y, 9);
            }

            $y -= 14;
        }

        if ($y < $bottom + 30) {
            $pdf->newPage();
            $y = $pdf->height - 50;
        }

        $pdf->line($margin, $y + 4, $pdf->width - $margin, $y + 4);
        $y -= 12;

        $totals = $report['totals'];
        $pdf->text('Total', $margin, $y, 9);
        $pdf->text((string) $totals['total'], $margin + 150, $y, 9);
        $pdf->text((string) $totals['aktif'], $margin + 190, $y, 9);
        $pdf->text((string) $totals['keluar'], $margin + 230, $y, 9);
        $pdf->text(number_format((float) $totals['turnover_rate'], 1, ',', '.'), $margin + 275, $y, 9);
        $pdf->text(number_format((float) $totals['total_gaji'], 0, ',', '.'), $margin + 340, $y, 9);

        $y -= 24;
        $pdf->text('Dicetak '.now()->format('d/m/Y H:i'), $margin, $y, 8);

        return $pdf->output();
    }

    /**
     * Susun angka laporan dari data HRIS.
     *
     * Rasio turnover dihitung dari jumlah karyawan yang **keluar dalam rentang
     * periode** dibagi total karyawan per departemen. Tanggal keluar
     * (`termination_date`) dipakai bila tersedia; karyawan berstatus non-aktif
     * tanpa tanggal keluar (data lama) tetap dihitung sebagai keluar agar
     * laporan tidak kehilangan angka.
     *
     * @return array<string, mixed>
     */
    private function report(Request $request): array
    {
        $from = $request->string('from')->toString() ?: CarbonImmutable::now()->startOfYear()->toDateString();
        $to = $request->string('to')->toString() ?: CarbonImmutable::now()->toDateString();

        $absences = Attendance::query()
            ->betweenDates($from, $to)
            ->absence()
            ->select('employee_id', DB::raw('count(*) as jumlah'))
            ->groupBy('employee_id')
            ->pluck('jumlah', 'employee_id');

        $departments = Employee::query()
            ->orderBy('department')
            ->get()
            ->groupBy('department')
            ->map(function ($employees, string $department) use ($absences, $from, $to) {
                $total = $employees->count();
                $aktif = $employees->where('status', true)->count();
                $nonAktif = $total - $aktif;

                $keluar = $employees->filter(function (Employee $employee) use ($from, $to) {
                    if ($employee->termination_date !== null) {
                        $tanggal = $employee->termination_date->toDateString();

                        return $tanggal >= $from && $tanggal <= $to;
                    }

                    return $employee->status === false;
                })->count();

                return [
                    'department' => $department,
                    'total' => $total,
                    'aktif' => $aktif,
                    'non_aktif' => $nonAktif,
                    'keluar' => $keluar,
                    'turnover_rate' => $total > 0 ? round($keluar / $total * 100, 1) : 0.0,
                    'total_gaji' => (float) $employees->where('status', true)->sum('gaji_bulanan'),
                    'rata_tidak_hadir' => $total > 0
                        ? round($employees->sum(fn (Employee $e) => (int) ($absences[$e->id] ?? 0)) / $total, 1)
                        : 0.0,
                ];
            })
            ->values();

        $totalGaji = (float) $departments->sum('total_gaji');
        $totalKaryawan = (int) $departments->sum('total');

        return [
            'from' => $from,
            'to' => $to,
            'departments' => $departments,
            'totals' => [
                'total' => $totalKaryawan,
                'aktif' => (int) $departments->sum('aktif'),
                'non_aktif' => (int) $departments->sum('non_aktif'),
                'keluar' => (int) $departments->sum('keluar'),
                'turnover_rate' => $totalKaryawan > 0
                    ? round((int) $departments->sum('keluar') / $totalKaryawan * 100, 1)
                    : 0.0,
                'total_gaji' => $totalGaji,
            ],
        ];
    }
}
