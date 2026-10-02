<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slip Gaji {{ $item->employee->name }} — {{ $period->periode }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 32px;
            color: #101828;
            background: #f2f4f7;
        }
        .sheet {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: 12px;
            padding: 32px;
        }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #667085; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; border-bottom: 2px solid #101828; padding-bottom: 16px; }
        .company { font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 14px; }
        th, td { text-align: left; padding: 8px 0; border-bottom: 1px solid #eaecf0; }
        td.amount, th.amount { text-align: right; font-variant-numeric: tabular-nums; }
        .total td { font-weight: 700; font-size: 16px; border-top: 2px solid #101828; border-bottom: none; }
        .meta { margin-top: 20px; font-size: 13px; }
        .meta dt { font-weight: 600; }
        .actions { max-width: 720px; margin: 16px auto 0; display: flex; gap: 8px; justify-content: flex-end; }
        .btn { border: 1px solid #d0d5dd; background: #fff; border-radius: 8px; padding: 8px 14px; font-size: 14px; cursor: pointer; text-decoration: none; color: #101828; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { border: none; border-radius: 0; padding: 0; max-width: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <a class="btn" href="{{ route('payroll-periods.show', $period) }}">Kembali</a>
        <button class="btn" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="sheet">
        <div class="head">
            <div>
                <h1>Slip Gaji</h1>
                <div class="company muted">HR Intelligence System</div>
            </div>
            <div class="company" style="text-align:right">
                <div><strong>Periode {{ $period->periode }}</strong></div>
                <div class="muted">Status: {{ $period->isFinal() ? 'Final' : ($period->isCalculated() ? 'Dihitung' : 'Draft') }}</div>
            </div>
        </div>

        <dl class="meta">
            <div><dt>Nama</dt><dd style="margin:0">{{ $item->employee->name }}</dd></div>
            <div><dt>NIK</dt><dd style="margin:0">{{ $item->employee->nik }}</dd></div>
            <div><dt>Departemen</dt><dd style="margin:0">{{ $item->departemen }}</dd></div>
            <div><dt>Level</dt><dd style="margin:0">{{ $item->level ?: '—' }}</dd></div>
        </dl>

        <table>
            <thead>
                <tr>
                    <th>Komponen</th>
                    <th class="amount">Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Gaji pokok</td>
                    <td class="amount">{{ number_format((float) $item->gaji_pokok, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan</td>
                    <td class="amount">{{ number_format((float) $item->tunjangan, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Lembur ({{ $item->lembur_jam }} jam &times; Rp {{ number_format((float) $item->tarif_lembur, 0, ',', '.') }})</td>
                    <td class="amount">{{ number_format((float) $item->biaya_lembur, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Potongan</td>
                    <td class="amount">-{{ number_format((float) $item->potongan, 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td>Total diterima</td>
                    <td class="amount">{{ number_format((float) $item->total_gaji, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <p class="muted" style="font-size:12px;margin-top:24px">
            Slip ini dihasilkan otomatis oleh sistem dan sah tanpa tanda tangan basah.
            @if ($period->finalized_at)
                Difinalisasi pada {{ $period->finalized_at->format('d M Y H:i') }}.
            @endif
        </p>
    </div>
</body>
</html>
