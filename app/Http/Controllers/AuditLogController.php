<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isManager(), 403);
        $logs = AuditLog::query()->with(['user', 'event'])->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))->when($request->filled('user'), fn ($query) => $query->where('user_id', $request->integer('user')))->when($request->filled('event'), fn ($query) => $query->where('event_id', $request->integer('event')))->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))->latest('created_at')->paginate(30)->withQueryString();

        return view('audit-logs.index', compact('logs'));
    }

    public function show(Request $request, AuditLog $auditLog): View
    {
        abort_unless($request->user()->isManager(), 403);
        $auditLog->load(['user', 'event', 'auditable']);

        return view('audit-logs.show', compact('auditLog'));
    }
}
