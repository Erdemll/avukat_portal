<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventUpdate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $events = Event::query()->visibleTo($request->user());
        $counts = ['total' => (clone $events)->count(), 'open' => (clone $events)->where('system_status', 'open')->count(), 'in_progress' => (clone $events)->where('system_status', 'in_progress')->count(), 'waiting' => (clone $events)->where('system_status', 'waiting')->count(), 'resolved' => (clone $events)->where('system_status', 'resolved')->count(), 'closed' => (clone $events)->where('system_status', 'closed')->count(), 'urgent' => (clone $events)->where('priority', 'urgent')->count()];

        $recentEvents = (clone $events)->with(['eventType', 'creator', 'assignedLawyer'])->latest('updated_at')->limit(10)->get();
        $unprocessedEvents = $request->user()->isLawyer()
            ? (clone $events)->whereIn('system_status', ['open', 'in_progress', 'waiting'])->doesntHave('updates')->with(['eventType', 'creator'])->latest('updated_at')->limit(10)->get()
            : collect();
        $recentUpdates = $request->user()->isManager()
            ? EventUpdate::query()->with(['event', 'user'])->latest()->limit(10)->get()
            : collect();
        $lawyerWorkloads = $request->user()->isManager()
            ? User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))
                ->withCount([
                    'assignedEvents as active_events_count' => fn ($query) => $query->whereIn('system_status', ['open', 'in_progress', 'waiting']),
                    'assignedEvents as urgent_events_count' => fn ($query) => $query->whereIn('system_status', ['open', 'in_progress', 'waiting'])->where('priority', 'urgent'),
                ])->orderBy('name')->get()
            : collect();

        return view('dashboard', compact('counts', 'recentEvents', 'unprocessedEvents', 'recentUpdates', 'lawyerWorkloads'));
    }
}
