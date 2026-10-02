@extends('layouts.app')

@section('title', 'Ajukan cuti')

@section('content')
    <h1>Ajukan cuti</h1>
    <p class="sub">Jumlah hari dihitung otomatis dari rentang tanggal.</p>

    @if ($errors->any())
        <div class="alert err">
            <ul style="margin:0 0 0 18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('leave-requests.store') }}" class="card">
        @csrf

        <div class="grid">
            <div class="field">
                <label for="employee_id">Karyawan</label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">— pilih —</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) old('employee_id', $leaveRequest->employee_id) === (string) $employee->id)>
                            {{ $employee->name }} ({{ $employee->nik }})
                        </option>
                    @endforeach
                </select>
                @error('employee_id') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="type">Jenis cuti</label>
                <select id="type" name="type" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('type', $leaveRequest->type) === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
                @error('type') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="start_date">Mulai</label>
                <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}" required>
                @error('start_date') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="end_date">Selesai</label>
                <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}" required>
                @error('end_date') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="field">
            <label for="reason">Alasan</label>
            <input id="reason" name="reason" value="{{ old('reason') }}" maxlength="1000">
            @error('reason') <div class="error">{{ $message }}</div> @enderror
        </div>

        @if ($balances)
            <p class="hint">
                Kuota {{ $balances['tahunan']->year }} —
                @foreach ($balances as $type => $balance)
                    {{ $type }}: <strong>{{ $balance->remaining_days }}</strong>/{{ $balance->entitled_days }}@if (! $loop->last) · @endif
                @endforeach
            </p>
        @endif

        <div class="row" style="margin-top:18px">
            <button type="submit" class="btn">Ajukan</button>
            <a href="{{ route('leave-requests.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
