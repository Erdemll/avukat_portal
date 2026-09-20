<?php

namespace App\Http\Controllers;

use App\Models\CaseFile;
use App\Models\Conversation;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', ['notifications' => $request->user()->notifications()->latest()->paginate(20)->withQueryString()]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();
        $data = $notification->data;

        if (($data['type'] ?? null) === 'message_received' && isset($data['conversation_id'])) {
            $conversation = Conversation::query()->find($data['conversation_id']);

            if ($conversation !== null && $request->user()->can('view', $conversation)) {
                return redirect()->route('messages.index', ['conversation' => $conversation->id]);
            }
        }

        if (in_array($data['type'] ?? null, ['case_assignment_requested', 'case_assignment_decided'], true)) {
            return redirect()->route('assignment-requests.index');
        }
        if (isset($data['case_file_id'])) {
            $caseFile = CaseFile::query()->find($data['case_file_id']);
            if ($caseFile !== null && $request->user()->can('view', $caseFile)) {
                return ($data['type'] ?? null) === 'financial_entry_created'
                    ? redirect()->route('financial-entries.index', ['case_file' => $caseFile->id])
                    : redirect()->route('case-files.show', $caseFile);
            }
        }
        if (isset($data['event_id'])) {
            $event = Event::query()->find($data['event_id']);
            if ($event !== null && $request->user()->can('view', $event)) {
                return redirect()->route('events.show', $event);
            }
        }

        return redirect()->route('notifications.index')->withErrors(['notification' => 'Bu bildirimin kaynağına artık erişiminiz yok.']);
    }
}
