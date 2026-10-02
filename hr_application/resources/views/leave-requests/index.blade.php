@extends('layouts.app')

@section('title', 'Pengajuan cuti')

@section('content')
    <div class="row">
        <div>
            <h1>Pengajuan cuti</h1>
            <p class="sub" style="margin-bottom:0">{{ $leaveRequests->total() }} pengajuan.</p>
        </div>
        <span style="flex:1"></span>
        <a href="{{ route('leave-requests.balances') }}" class="btn secondary">Saldo cuti</a>
        @can('create', App\Models\LeaveRequest::class)
            <a href="{{ route('leave-requests.create') }}" class="btn">Ajukan cuti</a>
        @endcan
    </div>

    @if ($errors->any())
        <div class="alert err" style="margin-top:16px">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('leave-requests.index') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
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
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
                <a href="{{ route('leave-requests.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Karyawan</th>
                <th>Jenis</th>
                <th>Rentang</th>
                <th>Hari</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leaveRequests as $leaveRequest)
                <tr>
                    <td>
                        <a href="{{ route('employees.show', $leaveRequest->employee_id) }}">{{ $leaveRequest->employee?->name ?? '—' }}</a>
                        <div class="muted" style="font-size:12px">{{ $leaveRequest->employee?->nik }}</div>
                    </td>
                    <td>{{ ucfirst($leaveRequest->type) }}</td>
                    <td>{{ $leaveRequest->start_date->format('d M') }} – {{ $leaveRequest->end_date->format('d M Y') }}</td>
                    <td>{{ $leaveRequest->days }}</td>
                    <td>
                        <span class="badge {{ $leaveRequest->status === 'approved' ? 'ok' : ($leaveRequest->status === 'rejected' ? 'off' : '') }}">
                            {{ ucfirst($leaveRequest->status) }}
                        </span>
                    </td>
                    <td class="actions">
                        <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="btn link">Detail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">Belum ada pengajuan cuti.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $leaveRequests->links('pagination::simple-tailwind') }}</div>
@endsection
