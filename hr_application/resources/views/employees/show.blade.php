@extends('layouts.app')

@section('title', $employee->name)

@section('content')
    <div class="row">
        <div>
            <h1>{{ $employee->name }}</h1>
            <p class="sub" style="margin-bottom:0">
                {{ $employee->nik }} · {{ $employee->position }} · {{ $employee->department }}
                <span class="badge {{ $employee->status ? 'ok' : 'off' }}">
                    {{ $employee->status ? 'Aktif' : 'Nonaktif' }}
                </span>
            </p>
        </div>
        <span style="flex:1"></span>
        @can('update', $employee)
            <a href="{{ route('employees.edit', $employee) }}" class="btn">Ubah</a>
        @endcan
    </div>

    <h2>Profil</h2>
    <div class="card">
        <dl>
            <dt>Email</dt><dd>{{ $employee->email }}</dd>
            <dt>Telepon</dt><dd>{{ $employee->phone ?: '—' }}</dd>
            <dt>Tanggal masuk</dt><dd>{{ $employee->join_date?->format('d M Y') ?? '—' }}</dd>
            <dt>Tanggal keluar</dt><dd>{{ $employee->termination_date?->format('d M Y') ?? '—' }}</dd>
            <dt>Alasan keluar</dt><dd>{{ $employee->termination_reason ?: '—' }}</dd>
            <dt>Masa kerja</dt><dd>{{ $employee->masa_kerja_bulan !== null ? $employee->masa_kerja_bulan.' bulan' : '—' }}</dd>
            <dt>Usia</dt><dd>{{ $employee->usia !== null ? $employee->usia.' tahun' : '—' }}</dd>
            <dt>Status kerja</dt><dd>{{ $employee->status_kerja ?: '—' }}</dd>
            <dt>Pendidikan</dt><dd>{{ $employee->pendidikan ?: '—' }}</dd>
            <dt>Gaji bulanan</dt><dd>{{ $employee->gaji_bulanan ? 'Rp '.number_format($employee->gaji_bulanan, 0, ',', '.') : '—' }}</dd>
            <dt>Jam lembur / bulan</dt><dd>{{ $employee->jam_lembur_bulanan !== null ? $employee->jam_lembur_bulanan.' jam' : '—' }}</dd>
            <dt>Jarak rumah–kantor</dt><dd>{{ $employee->jarak_km !== null ? $employee->jarak_km.' km' : '—' }}</dd>
            <dt>Kepuasan kerja</dt><dd>{{ $employee->kepuasan_kerja !== null ? $employee->kepuasan_kerja.' / 5' : '—' }}</dd>
        </dl>
    </div>

    <h2>Riwayat prediksi ML</h2>
    @if ($predictions->isEmpty())
        <div class="card muted">
            Belum ada prediksi untuk karyawan ini. Jalankan prediksi batch dari menu
            <a href="{{ route('hr-intelligence.use-cases') }}">Prediksi ML</a>.
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Use case</th>
                    <th>Skor</th>
                    <th>Label</th>
                    <th>Model</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($predictions as $prediction)
                    <tr>
                        <td>{{ $prediction->use_case }}</td>
                        <td>{{ $prediction->skor !== null ? number_format($prediction->skor, 4, ',', '.') : '—' }}</td>
                        <td>
                            <span class="badge {{ $prediction->is_flag ? 'off' : 'ok' }}">
                                {{ $prediction->label ?: '—' }}
                            </span>
                        </td>
                        <td class="muted">{{ $prediction->model_version ?: '—' }}</td>
                        <td class="muted">{{ $prediction->predicted_at?->format('d M Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Riwayat karyawan</h2>
    @if ($histories->isEmpty())
        <div class="card muted">Belum ada riwayat perubahan (mutasi, promosi, gaji, status).</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Peristiwa</th>
                    <th>Dari</th>
                    <th>Menjadi</th>
                    <th>Oleh</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($histories as $history)
                    <tr>
                        <td class="muted">{{ $history->created_at?->format('d M Y H:i') }}</td>
                        <td><strong>{{ $history->label() }}</strong></td>
                        <td class="muted">{{ $history->old_value ?: '—' }}</td>
                        <td>{{ $history->new_value ?: '—' }}</td>
                        <td class="muted">{{ $history->changedBy?->name ?? 'Sistem' }}</td>
                        <td class="muted">{{ $history->note ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @can('update', $employee)
        @if (! $employee->has_terminated)
            <h2>Hentikan karyawan</h2>
            <div class="card">
                <p style="margin-top:0">
                    Tandai karyawan ini berhenti kerja. Status menjadi nonaktif dan tanggal
                    keluar dipakai untuk laporan turnover per periode.
                </p>
                <form method="POST" action="{{ route('employees.terminate', $employee) }}">
                    @csrf
                    <div class="grid">
                        <div class="field">
                            <label for="termination_date">Tanggal keluar</label>
                            <input id="termination_date" name="termination_date" type="date" value="{{ old('termination_date', now()->toDateString()) }}" required>
                            @error('termination_date') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="termination_reason">Alasan</label>
                            <select id="termination_reason" name="termination_reason" required>
                                <option value="">— pilih —</option>
                                @foreach (App\Models\Employee::TERMINATION_REASONS as $reason)
                                    <option value="{{ $reason }}" @selected(old('termination_reason') === $reason)>{{ $reason }}</option>
                                @endforeach
                            </select>
                            @error('termination_reason') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="termination_note">Catatan (opsional)</label>
                            <input id="termination_note" name="termination_note" value="{{ old('termination_note') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn" style="margin-top:12px">Tandai berhenti kerja</button>
                </form>
            </div>
        @endif
    @endcan

    @can('delete', $employee)
        <h2>Hapus</h2>
        <div class="card">
            <p style="margin-top:0">
                Menghapus karyawan akan menghilangkan datanya dari daftar. Riwayat prediksi
                yang sudah tersimpan tidak ikut terhapus.
            </p>
            <form method="POST" action="{{ route('employees.destroy', $employee) }}"
                  onsubmit="return confirm('Hapus {{ $employee->name }}? Tindakan ini tidak dapat dibatalkan.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Hapus karyawan</button>
            </form>
        </div>
    @endcan
@endsection
