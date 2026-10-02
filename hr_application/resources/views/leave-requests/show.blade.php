@extends('layouts.app')

@section('title', 'Pengajuan cuti')

@section('content')
    <div class="row">
        <div>
            <h1>{{ $leaveRequest->employee?->name }}</h1>
            <p class="sub" style="margin-bottom:0">
                {{ ucfirst($leaveRequest->type) }} ·
                {{ $leaveRequest->start_date->format('d M Y') }} – {{ $leaveRequest->end_date->format('d M Y') }} ·
                {{ $leaveRequest->days }} hari
                <span class="badge {{ $leaveRequest->status === 'approved' ? 'ok' : ($leaveRequest->status === 'rejected' ? 'off' : '') }}">
                    {{ ucfirst($leaveRequest->status) }}
                </span>
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert err" style="margin-top:16px">{{ $errors->first() }}</div>
    @endif

    <h2>Detail</h2>
    <div class="card">
        <dl>
            <dt>Karyawan</dt><dd><a href="{{ route('employees.show', $leaveRequest->employee_id) }}">{{ $leaveRequest->employee?->name }} ({{ $leaveRequest->employee?->nik }})</a></dd>
            <dt>Jenis</dt><dd>{{ ucfirst($leaveRequest->type) }}</dd>
            <dt>Rentang</dt><dd>{{ $leaveRequest->start_date->format('d M Y') }} – {{ $leaveRequest->end_date->format('d M Y') }}</dd>
            <dt>Jumlah hari</dt><dd>{{ $leaveRequest->days }}</dd>
            <dt>Alasan</dt><dd>{{ $leaveRequest->reason ?: '—' }}</dd>
            <dt>Diputuskan oleh</dt><dd>{{ $leaveRequest->decidedBy?->name ?? '—' }}</dd>
            <dt>Waktu keputusan</dt><dd>{{ $leaveRequest->decided_at?->format('d M Y H:i') ?? '—' }}</dd>
            <dt>Catatan keputusan</dt><dd>{{ $leaveRequest->decision_note ?: '—' }}</dd>
        </dl>
    </div>

    @if ($leaveRequest->consumesBalance())
        <h2>Saldo cuti {{ $balance->year }}</h2>
        <div class="card">
            Tersisa <strong>{{ $balance->remaining_days }}</strong> dari {{ $balance->entitled_days }} hari
            (terpakai {{ $balance->used_days }}).
        </div>
    @endif

    @can('decide', $leaveRequest)
        <h2>Keputusan</h2>
        <div class="card">
            <form method="POST" action="{{ route('leave-requests.approve', $leaveRequest) }}" style="margin-bottom:14px">
                @csrf
                <div class="field">
                    <label for="approve_note">Catatan (opsional)</label>
                    <input id="approve_note" name="decision_note" maxlength="255">
                </div>
                <button type="submit" class="btn">Setujui</button>
            </form>

            <form method="POST" action="{{ route('leave-requests.reject', $leaveRequest) }}">
                @csrf
                <div class="field">
                    <label for="reject_note">Catatan penolakan (opsional)</label>
                    <input id="reject_note" name="decision_note" maxlength="255">
                </div>
                <button type="submit" class="btn danger">Tolak</button>
            </form>
        </div>
    @endcan

    @can('delete', $leaveRequest)
        <h2>Hapus</h2>
        <div class="card">
            <form method="POST" action="{{ route('leave-requests.destroy', $leaveRequest) }}"
                  onsubmit="return confirm('Hapus pengajuan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Hapus pengajuan</button>
            </form>
        </div>
    @endcan
@endsection
