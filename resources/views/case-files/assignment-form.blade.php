<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="font-semibold text-slate-900">Avukat Atamaları</h2>
    <form class="mt-4 space-y-3" method="POST" action="{{ route('case-files.assignments.update', $caseFile) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="lock_version" value="{{ $caseFile->lock_version }}">
        <div class="max-h-56 space-y-2 overflow-y-auto">
            @foreach($lawyers as $lawyer)
                <label class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 p-2 text-sm">
                    <span><input type="checkbox" name="lawyer_ids[]" value="{{ $lawyer->id }}" @checked($activeLawyerIds->contains($lawyer->id))> {{ $lawyer->name }}</span>
                    <span class="text-xs"><input type="radio" name="lead_lawyer_id" value="{{ $lawyer->id }}" @checked($leadAssignment?->lawyer_id === $lawyer->id)> Lider</span>
                </label>
            @endforeach
        </div>
        <textarea name="reason" rows="2" placeholder="Atama/değişiklik gerekçesi" class="block w-full rounded-lg border-slate-300"></textarea>
        @error('lawyer_ids')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        @error('lead_lawyer_id')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        @error('lock_version')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        <button class="w-full rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Atamaları Güncelle</button>
    </form>
</section>
