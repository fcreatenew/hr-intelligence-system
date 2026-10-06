@extends('layouts.app')

@section('title', $training->title)

@section('content')
    <div class="row">
        <div>
            <h1>{{ $training->title }}</h1>
            <p class="sub" style="margin-bottom:0">
                {{ ucfirst(str_replace('_', ' ', $training->category)) }} ·
                {{ ucfirst($training->mode) }}
                @if ($training->provider) · {{ $training->provider }} @endif
            </p>
        </div>
        <span style="flex:1"></span>
        @can('update', $training)
            <a href="{{ route('trainings.edit', $training) }}" class="btn secondary">Ubah</a>
        @endcan
        <a href="{{ route('trainings.index') }}" class="btn secondary">Kembali</a>
    </div>

    <div class="card" style="margin-top:18px">
        <dl>
            <dt>Jadwal</dt>
            <dd>
                {{ $training->start_date?->format('d M Y') }}
                @if ($training->end_date) – {{ $training->end_date->format('d M Y') }} @endif
            </dd>
            <dt>Durasi</dt>
            <dd>{{ $training->duration_hours ? $training->duration_hours.' jam' : '—' }}</dd>
            <dt>Biaya</dt>
            <dd>Rp {{ number_format($training->cost, 0, ',', '.') }}</dd>
            <dt>Kuota</dt>
            <dd>{{ $training->quota ?? 'Tidak dibatasi' }}</dd>
            <dt>Status</dt>
            <dd><span class="badge {{ $training->status === 'selesai' ? 'ok' : '' }}">{{ ucfirst($training->status) }}</span></dd>
            <dt>Deskripsi</dt>
            <dd>{{ $training->description ?: '—' }}</dd>
        </dl>
    </div>

    <h2>Peserta ({{ $participants->count() }})</h2>

    @can('manageParticipants', $training)
        <form method="POST" action="{{ route('trainings.participants.store', $training) }}" class="card" style="margin-bottom:16px">
            @csrf
            <div class="field" style="margin:0">
                <label for="employee_ids">Daftarkan karyawan</label>
                <select id="employee_ids" name="employee_ids[]" multiple size="6">
                    @foreach ($availableEmployees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }} — {{ $employee->nik }}</option>
                    @endforeach
                </select>
                <div class="hint">Tahan Ctrl/Cmd untuk memilih beberapa karyawan.</div>
            </div>
            <button type="submit" class="btn" style="margin-top:10px">Daftarkan</button>
        </form>
    @endcan

    <table>
        <thead>
            <tr>
                <th>Karyawan</th>
                <th>Status</th>
                <th>Skor</th>
                <th>Sertifikat</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($participants as $participant)
                <tr>
                    <td>
                        <a href="{{ route('employees.show', $participant->employee_id) }}">{{ $participant->employee?->name ?? '—' }}</a>
                        <div class="muted" style="font-size:12px">{{ $participant->employee?->nik }}</div>
                    </td>
                    <td><span class="badge {{ $participant->status === 'selesai' ? 'ok' : '' }}">{{ ucfirst($participant->status) }}</span></td>
                    <td>{{ $participant->score ?? '—' }}</td>
                    <td>
                        @if ($participant->certified)
                            <span class="badge ok">Ya</span>
                            @if ($participant->certificate_number)
                                <div class="muted" style="font-size:12px">{{ $participant->certificate_number }}</div>
                            @endif
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td class="actions">
                        @can('manageParticipants', $training)
                            <form method="POST" action="{{ route('participants.update', $participant) }}" class="row">
                                @csrf
                                @method('PUT')
                                <select name="status" style="width:auto">
                                    @foreach (\App\Models\TrainingParticipant::STATUSES as $status)
                                        <option value="{{ $status }}" @selected($participant->status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                                <input name="score" type="number" min="0" max="100" placeholder="Skor"
                                       value="{{ $participant->score }}" style="width:80px">
                                <label class="row" style="gap:4px;font-weight:400">
                                    <input type="hidden" name="certified" value="0">
                                    <input type="checkbox" name="certified" value="1" @checked($participant->certified) style="width:auto"> Sertifikat
                                </label>
                                <input name="certificate_number" placeholder="No. sertifikat"
                                       value="{{ $participant->certificate_number }}" style="width:150px">
                                <button type="submit" class="btn link">Simpan</button>
                            </form>
                            <form method="POST" action="{{ route('participants.destroy', $participant) }}"
                                  onsubmit="return confirm('Hapus partisipan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn link">Hapus</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted" style="padding:24px;text-align:center">Belum ada peserta.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
