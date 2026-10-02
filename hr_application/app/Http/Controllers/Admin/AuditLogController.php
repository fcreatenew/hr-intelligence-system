<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Jejak audit: siapa mengubah data HR apa, kapan, dan dari nilai apa.
 * Hanya admin yang dapat melihat seluruh jejak.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->string('event')))
            ->when($request->filled('type'), fn ($q) => $q->where('auditable_type', $request->string('type')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'events' => [AuditLog::EVENT_CREATED, AuditLog::EVENT_UPDATED, AuditLog::EVENT_DELETED],
            'types' => AuditLog::query()
                ->select('auditable_type')
                ->distinct()
                ->orderBy('auditable_type')
                ->pluck('auditable_type'),
            'filters' => [
                'event' => $request->string('event')->toString(),
                'type' => $request->string('type')->toString(),
            ],
        ]);
    }
}
