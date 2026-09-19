<?php

namespace App\Http\Controllers;

use App\Models\Hearing;
use App\Services\LegalCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LegalCalendarController extends Controller
{
    public function __invoke(Request $request, LegalCalendarService $calendar): View
    {
        Gate::authorize('viewAny', Hearing::class);
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString())->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString())->endOfDay() : now()->addMonths(2)->endOfMonth();
        abort_if($from->diffInDays($to) > 370, 422, 'Takvim aralığı bir yıldan uzun olamaz.');

        return view('legal-calendar.index', [
            'entriesByDate' => $calendar->entries($request->user(), $from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
