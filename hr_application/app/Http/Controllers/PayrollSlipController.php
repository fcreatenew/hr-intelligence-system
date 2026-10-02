<?php

namespace App\Http\Controllers;

use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use Illuminate\View\View;

class PayrollSlipController extends Controller
{
    /**
     * Slip gaji satu karyawan untuk satu periode, siap dicetak/disimpan PDF
     * lewat dialog cetak peramban.
     */
    public function show(PayrollPeriod $payrollPeriod, PayrollItem $item): View
    {
        $this->authorize('view', $payrollPeriod);

        abort_unless($item->payroll_period_id === $payrollPeriod->id, 404);

        $item->loadMissing('employee');

        return view('payroll-periods.slip', [
            'period' => $payrollPeriod,
            'item' => $item,
        ]);
    }
}
