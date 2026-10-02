<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HRIS') — {{ config('app.name', 'HR Application') }}</title>
    <style>
        :root { --line:#e3e3e0; --muted:#706f6c; --accent:#1b1b18; --danger:#b42318; --ok:#067647; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
               color:var(--accent); background:#fdfdfc; font-size:14px; line-height:1.5; }
        a { color:var(--accent); }
        header { border-bottom:1px solid var(--line); background:#fff; }
        .bar { max-width:1100px; margin:0 auto; padding:14px 20px; display:flex; align-items:center; gap:20px; }
        .bar nav { display:flex; gap:16px; }
        .bar .spacer { flex:1; }
        main { max-width:1100px; margin:0 auto; padding:28px 20px 60px; }
        h1 { font-size:22px; margin:0 0 4px; }
        h2 { font-size:16px; margin:28px 0 10px; }
        .sub { color:var(--muted); margin:0 0 22px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:8px; padding:20px; }
        table { width:100%; border-collapse:collapse; background:#fff; }
        th, td { text-align:left; padding:10px 12px; border-bottom:1px solid var(--line); }
        th { font-weight:600; color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
        tbody tr:last-child td { border-bottom:none; }
        .btn { display:inline-block; padding:8px 14px; border-radius:6px; border:1px solid var(--accent);
               background:var(--accent); color:#fff; text-decoration:none; cursor:pointer; font-size:14px; }
        .btn.secondary { background:#fff; color:var(--accent); }
        .btn.danger { background:var(--danger); border-color:var(--danger); }
        .btn.link { background:none; border:none; color:var(--muted); padding:0; text-decoration:underline; }
        .row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; }
        label { display:block; font-weight:600; margin-bottom:4px; font-size:13px; }
        input, select { width:100%; padding:8px 10px; border:1px solid var(--line); border-radius:6px;
                        font-size:14px; font-family:inherit; background:#fff; }
        .field { margin-bottom:14px; }
        .hint { color:var(--muted); font-size:12px; margin-top:4px; }
        .error { color:var(--danger); font-size:12px; margin-top:4px; }
        .alert { padding:10px 14px; border-radius:6px; margin-bottom:18px; border:1px solid; }
        .alert.ok { border-color:#abefc6; background:#ecfdf3; color:var(--ok); }
        .alert.err { border-color:#fecdca; background:#fef3f2; color:var(--danger); }
        .badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:12px; border:1px solid var(--line); }
        .badge.ok { color:var(--ok); border-color:#abefc6; background:#ecfdf3; }
        .badge.off { color:var(--muted); background:#f4f4f3; }
        .muted { color:var(--muted); }
        .actions { display:flex; gap:8px; }
        .pagination { margin-top:16px; }
        .pagination svg { width:14px; height:14px; vertical-align:middle; }
        dl { display:grid; grid-template-columns:200px 1fr; gap:8px 16px; margin:0; }
        dt { color:var(--muted); }
        dd { margin:0; }
    </style>
</head>
<body>
    <header>
        <div class="bar">
            <strong><a href="{{ route('employees.index') }}" style="text-decoration:none">HRIS</a></strong>
            @auth
                <nav>
                    <a href="{{ route('employees.index') }}">Karyawan</a>
                    <a href="{{ route('attendances.index') }}">Absensi</a>
                    <a href="{{ route('leave-requests.index') }}">Cuti</a>
                    <a href="{{ route('hr-intelligence.use-cases') }}">Prediksi ML</a>
                </nav>
            @endauth
            <span class="spacer"></span>
            @auth
                <span class="muted">{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn link">Keluar</button>
                </form>
            @endauth
        </div>
    </header>

    <main>
        @if (session('status'))
            <div class="alert ok">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
