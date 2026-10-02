@extends('layouts.app')

@section('title', 'Absensi')

@section('content')
    <div class="row">
        <div>
            <h1>Absensi</h1>
            <p class="sub" style="margin-bottom:0">{{ $attendances->total() }} catatan pada rentang ini.</p>
        </div>
        <span style="flex:1"></span>
        <a href="{{ route('attendances.recap') }}" class="btn secondary">Rekap</a>
        @can('create', App\Models\Attendance::class)
            <a href="{{ route('attendances.import-template') }}" class="btn secondary">Template CSV</a>
            <a href="{{ route('attendances.create') }}" class="btn">Tambah catatan</a>
        @endcan
    </div>

    @if ($errors->any())
        <div class="alert err" style="margin-top:16px">{{ $errors->first() }}</div>
    @endif

    @if (session('import_skipped'))
        <div class="alert err" style="margin-top:16px">
            <strong>Baris yang dilewati:</strong>
            <ul style="margin:8px 0 0 18px">
                @foreach (session('import_skipped') as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @can('create', App\Models\Attendance::class)
        <details class="card" style="margin:18px 0">
            <summary style="cursor:pointer;font-weight:600">Impor dari mesin absensi (CSV)</summary>
            <p class="hint" style="margin-top:10px">
                Kolom: <code>nik,date,in,out,status</code>. Status opsional; bila kosong diturunkan dari jam masuk.
                Menit terlambat dan lembur dihitung dari jam masuk/keluar memakai jam kerja di konfigurasi.
                Baris dengan NIK tak dikenal atau tanggal tidak valid dilewati dan dilaporkan.
            </p>
            <form method="POST" action="{{ route('attendances.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="field">
                    <label for="file">Berkas CSV</label>
                    <input id="file" name="file" type="file" accept=".csv,text/csv" required>
                </div>
                <button type="submit" class="btn">Impor</button>
            </form>
        </details>
    @endcan

    <form method="GET" action="{{ route('attendances.index') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="from">Dari</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] }}">
            </div>
            <div class="field" style="margin:0">
                <label for="to">Sampai</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] }}">
            </div>
            <div class="field" style="margin:0">
                <label for="employee_id">Karyawan</label>
                <select id="employee_id" name="employee_id">
                    <option value="">Semua</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($filters['employee_id'] === (string) $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="margin:0">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
                <a href="{{ route('attendances.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Karyawan</th>
                <th>Status</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Terlambat</th>
                <th>Lembur</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->date->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('employees.show', $attendance->employee_id) }}">{{ $attendance->employee?->name ?? '—' }}</a>
                        <div class="muted" style="font-size:12px">{{ $attendance->employee?->nik }}</div>
                    </td>
                    <td><span class="badge {{ in_array($attendance->status, ['izin','sakit','alpha']) ? 'off' : 'ok' }}">{{ ucfirst($attendance->status) }}</span></td>
                    <td>{{ $attendance->check_in ? \Illuminate\Support\Str::substr($attendance->check_in, 0, 5) : '—' }}</td>
                    <td>{{ $attendance->check_out ? \Illuminate\Support\Str::substr($attendance->check_out, 0, 5) : '—' }}</td>
                    <td>{{ $attendance->late_minutes ?: '—' }}</td>
                    <td>{{ $attendance->overtime_minutes ?: '—' }}</td>
                    <td class="actions">
                        @can('update', $attendance)
                            <a href="{{ route('attendances.edit', $attendance) }}" class="btn link">Ubah</a>
                        @endcan
                        @can('delete', $attendance)
                            <form method="POST" action="{{ route('attendances.destroy', $attendance) }}"
                                  onsubmit="return confirm('Hapus catatan absensi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn link" style="color:#b42318">Hapus</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted" style="padding:24px;text-align:center">Belum ada catatan absensi pada rentang ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $attendances->links('pagination::simple-tailwind') }}</div>
@endsection
