<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdvancedSearchRequest;
use App\Models\CaseFile;
use App\Models\Client;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\Hearing;
use App\Models\LegalTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdvancedSearchController extends Controller
{
    public function __invoke(AdvancedSearchRequest $request): View
    {
        $data = $request->validated();
        $term = trim((string) ($data['q'] ?? ''));
        $type = $data['type'] ?? 'all';
        $empty = collect();

        if ($term === '') {
            return view('search.index', compact('term', 'type') + [
                'caseFiles' => $empty,
                'documents' => $empty,
                'clients' => $empty,
                'hearings' => $empty,
                'deadlines' => $empty,
                'tasks' => $empty,
            ]);
        }

        $like = '%'.addcslashes($term, '%_\\').'%';
        $user = $request->user();
        $include = fn (string $group): bool => $type === 'all' || $type === $group;

        $caseFiles = $include('case_files') ? CaseFile::query()
            ->visibleTo($user)
            ->with('caseType')
            ->where(function (Builder $query) use ($like): void {
                $query->where('case_no', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhereHas('proceedings', fn (Builder $proceedings) => $proceedings
                        ->where('authority_name', 'like', $like)
                        ->orWhere('external_file_number', 'like', $like)
                        ->orWhere('principal_number', 'like', $like));
            })->latest('updated_at')->limit(10)->get() : $empty;

        $documents = $include('documents') ? Document::query()
            ->visibleTo($user)
            ->with(['caseFile', 'event'])
            ->whereNull('archived_at')
            ->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('original_name', 'like', $like))
            ->latest()->limit(10)->get() : $empty;

        $clients = $include('clients') ? Client::query()
            ->visibleTo($user)
            ->with('party')
            ->whereHas('party', fn (Builder $party) => $party
                ->where('name', 'like', $like)
                ->orWhere('surname', 'like', $like)
                ->orWhere('company_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like))
            ->latest()->limit(10)->get() : $empty;

        [$hearings, $deadlines, $tasks] = $include('calendar')
            ? $this->calendarResults($user, $like, $data['date_from'] ?? null, $data['date_to'] ?? null)
            : [$empty, $empty, $empty];

        return view('search.index', compact('term', 'type', 'caseFiles', 'documents', 'clients', 'hearings', 'deadlines', 'tasks'));
    }

    /** @return array{Collection, Collection, Collection} */
    private function calendarResults(User $user, string $like, ?string $from, ?string $to): array
    {
        $hearings = Hearing::query()->visibleTo($user)->with('caseFile')
            ->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('court', 'like', $like))
            ->when($from, fn (Builder $query, string $date) => $query->whereDate('hearing_at', '>=', $date))
            ->when($to, fn (Builder $query, string $date) => $query->whereDate('hearing_at', '<=', $date))
            ->orderBy('hearing_at')->limit(10)->get();

        $deadlines = Deadline::query()->visibleTo($user)->with('caseFile')
            ->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->when($from, fn (Builder $query, string $date) => $query->whereDate('due_at', '>=', $date))
            ->when($to, fn (Builder $query, string $date) => $query->whereDate('due_at', '<=', $date))
            ->orderBy('due_at')->limit(10)->get();

        $tasks = LegalTask::query()->visibleTo($user)->with('caseFile')
            ->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->when($from, fn (Builder $query, string $date) => $query->whereDate('due_at', '>=', $date))
            ->when($to, fn (Builder $query, string $date) => $query->whereDate('due_at', '<=', $date))
            ->orderBy('due_at')->limit(10)->get();

        return [$hearings, $deadlines, $tasks];
    }
}
