@php
    $leadAssignment = $caseFile->assignments->first(fn ($assignment) => is_null($assignment->ended_at) && $assignment->role === App\CaseAssignmentRole::Lead);
    $activeLawyerIds = $caseFile->activeLawyers->pluck('id');
@endphp
<x-layouts.app :title="$caseFile->case_no.' Avukat Atamaları'">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a class="text-sm font-medium text-slate-500 hover:text-slate-900" href="{{ route('case-files.index') }}">&larr; Hukuki Dosyalar</a>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <span class="font-mono text-sm font-bold text-indigo-600">{{ $caseFile->case_no }}</span>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $caseFile->status->badgeClass() }}">{{ $caseFile->status->label() }}</span>
                <span class="rounded-md bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $caseFile->caseType->name }}</span>
            </div>
            <h1 class="mt-2 break-words text-2xl font-bold text-slate-950">{{ $caseFile->title }}</h1>
            <p class="mt-2 text-sm text-slate-500">Lider avukat: {{ $leadAssignment?->lawyer?->name ?? 'Belirlenmedi' }}</p>
        </div>
        @include('case-files.assignment-form')
    </div>
</x-layouts.app>
