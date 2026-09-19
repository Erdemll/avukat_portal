<?php

namespace App\Http\Controllers\Admin;

use App\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManagedUserRequest;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Models\CaseFileAssignment;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $users = User::query()->with('role')->when($request->filled('search'), fn ($query) => $query->where(fn ($subquery) => $subquery->where('name', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%')))->when($request->filled('role'), fn ($query) => $query->where('role_id', $request->integer('role')))->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.form', ['user' => new User, 'roles' => Role::query()->where('is_active', true)->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreManagedUserRequest $request, AuditService $audit): RedirectResponse
    {
        Gate::authorize('create', User::class);
        $user = DB::transaction(function () use ($request, $audit): User {
            $user = new User($request->validated());
            $user->password = Str::password(32);
            $user->save();
            $audit->log(AuditAction::UserCreated, $request->user(), auditable: $user, description: 'Kullanıcı oluşturuldu.', newValues: ['user_id' => $user->id, 'role_id' => $user->role_id]);

            return $user;
        });

        return redirect()->route('admin.users.edit', $user);
    }

    /**
     * Display the specified resource.
     */
    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.form', ['user' => $user, 'roles' => Role::query()->where('is_active', true)->get()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateManagedUserRequest $request, User $user, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $user);
        DB::transaction(function () use ($request, $user, $audit): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $changingRole = $lockedUser->role_id !== $request->integer('role_id');
            if ($changingRole && $lockedUser->isLawyer() && $this->lawyerHasActiveWork($lockedUser)) {
                throw ValidationException::withMessages(['role_id' => 'Bu avukata atanmış aktif işler bulunduğu için rolü değiştirilemez.']);
            }
            $oldRole = $lockedUser->role_id;
            $lockedUser->fill($request->validated())->save();
            $audit->log($changingRole ? AuditAction::UserRoleChanged : AuditAction::UserUpdated, $request->user(), auditable: $lockedUser, description: 'Kullanıcı güncellendi.', oldValues: $changingRole ? ['role_id' => $oldRole] : [], newValues: $changingRole ? ['role_id' => $lockedUser->role_id] : []);
        });

        return redirect()->route('admin.users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function activate(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $user);
        DB::transaction(function () use ($request, $user, $audit): void {
            $user->forceFill(['is_active' => true])->save();
            $audit->log(AuditAction::UserActivated, $request->user(), auditable: $user, description: 'Kullanıcı aktifleştirildi.', newValues: ['user_id' => $user->id]);
        });

        return back();
    }

    public function deactivate(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $user);
        DB::transaction(function () use ($request, $user, $audit): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $activeManagers = User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'manager'))->lockForUpdate()->get();
            if ($lockedUser->id === $request->user()->id || ($lockedUser->isManager() && $activeManagers->count() === 1)) {
                throw ValidationException::withMessages(['user' => 'Sistemde en az bir aktif yönetici kalmalıdır.']);
            }
            if ($lockedUser->isLawyer() && $this->lawyerHasActiveWork($lockedUser)) {
                throw ValidationException::withMessages(['user' => 'Bu avukata atanmış aktif işler bulunduğu için pasifleştirilemez.']);
            }
            $lockedUser->forceFill(['is_active' => false])->save();
            $audit->log(AuditAction::UserDeactivated, $request->user(), auditable: $lockedUser, description: 'Kullanıcı pasifleştirildi.', newValues: ['user_id' => $lockedUser->id]);
        });

        return back();
    }

    public function sendPasswordReset(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $user);
        if (! $user->is_active) {
            return back()->withErrors(['user' => 'Pasif kullanıcıya parola oluşturma/sıfırlama bağlantısı gönderilemez.']);
        }
        try {
            $status = Password::sendResetLink(['email' => $user->email]);
            if ($status !== Password::ResetLinkSent) {
                return back()->withErrors(['user' => 'Parola sıfırlama e-postası gönderilemedi.']);
            }
            $audit->safelyLog(AuditAction::PasswordResetRequested, $request->user(), description: 'Yönetici parola sıfırlama bağlantısı gönderdi.', newValues: ['target_user_id' => $user->id]);

            return back()->with('status', 'Parola oluşturma/sıfırlama bağlantısı kullanıcıya gönderildi.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['user' => 'Parola sıfırlama e-postası gönderilemedi.']);
        }
    }

    private function lawyerHasActiveWork(User $user): bool
    {
        return Event::query()
            ->where('assigned_lawyer_id', $user->id)
            ->where('system_status', '!=', 'closed')
            ->exists()
            || CaseFileAssignment::query()
                ->where('lawyer_id', $user->id)
                ->whereNull('ended_at')
                ->whereHas('caseFile', fn ($query) => $query->where('status', '!=', 'closed'))
                ->exists();
    }
}
