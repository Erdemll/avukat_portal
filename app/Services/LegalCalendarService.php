<?php

namespace App\Services;

use App\Models\Deadline;
use App\Models\Hearing;
use App\Models\LegalTask;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class LegalCalendarService
{
    /** @return Collection<string, Collection<int, array<string, mixed>>> */
    public function entries(User $user, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $hearings = Hearing::query()->visibleTo($user)->with('caseFile')
            ->whereBetween('hearing_at', [$from, $to])->get()
            ->map(fn (Hearing $hearing): array => [
                'at' => $hearing->hearing_at,
                'type' => 'hearing',
                'type_label' => 'Duruşma',
                'title' => $hearing->title,
                'case_no' => $hearing->caseFile->case_no,
                'status' => $hearing->status->label(),
                'url' => route('hearings.edit', $hearing),
            ]);
        $deadlines = Deadline::query()->visibleTo($user)->with('caseFile')
            ->whereBetween('due_at', [$from, $to])->get()
            ->map(fn (Deadline $deadline): array => [
                'at' => $deadline->due_at,
                'type' => 'deadline',
                'type_label' => 'Süre',
                'title' => $deadline->title,
                'case_no' => $deadline->caseFile->case_no,
                'status' => $deadline->status->label(),
                'url' => route('deadlines.edit', $deadline),
            ]);
        $tasks = LegalTask::query()->visibleTo($user)->with('caseFile')
            ->whereNotNull('due_at')->whereBetween('due_at', [$from, $to])->get()
            ->map(fn (LegalTask $task): array => [
                'at' => $task->due_at,
                'type' => 'task',
                'type_label' => 'Görev',
                'title' => $task->title,
                'case_no' => $task->caseFile?->case_no,
                'status' => $task->status->label(),
                'url' => route('legal-tasks.edit', $task),
            ]);

        return $hearings->concat($deadlines)->concat($tasks)
            ->sortBy('at')
            ->groupBy(fn (array $entry): string => $entry['at']->toDateString());
    }
}
