<?php

use App\Http\Controllers\Admin\EventTypeController as AdminEventTypeController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventUpdateController;
use App\Http\Controllers\NewPasswordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::resource('events', EventController::class);
    Route::patch('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.status.update');
    Route::patch('/events/{event}/priority', [EventController::class, 'updatePriority'])->name('events.priority.update');
    Route::patch('/events/{event}/assign-lawyer', [EventController::class, 'reassignLawyer'])->name('events.lawyer.update');
    Route::post('/events/{event}/updates', [EventUpdateController::class, 'store'])->name('events.updates.store');
    Route::post('/events/{event}/documents', [DocumentController::class, 'store'])->name('events.documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/admin/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::post('/admin/users/{user}/activate', [AdminUserController::class, 'activate'])->name('admin.users.activate');
    Route::post('/admin/users/{user}/deactivate', [AdminUserController::class, 'deactivate'])->name('admin.users.deactivate');
    Route::post('/admin/users/{user}/send-password-reset', [AdminUserController::class, 'sendPasswordReset'])->middleware('throttle:manager-password-reset')->name('admin.users.send-password-reset');
    Route::get('/admin/event-types', [AdminEventTypeController::class, 'index'])->name('admin.event-types.index');
    Route::get('/admin/event-types/create', [AdminEventTypeController::class, 'create'])->name('admin.event-types.create');
    Route::post('/admin/event-types', [AdminEventTypeController::class, 'store'])->name('admin.event-types.store');
    Route::get('/admin/event-types/{eventType}/edit', [AdminEventTypeController::class, 'edit'])->name('admin.event-types.edit');
    Route::put('/admin/event-types/{eventType}', [AdminEventTypeController::class, 'update'])->name('admin.event-types.update');
    Route::post('/admin/event-types/{eventType}/activate', [AdminEventTypeController::class, 'activate'])->name('admin.event-types.activate');
    Route::post('/admin/event-types/{eventType}/deactivate', [AdminEventTypeController::class, 'deactivate'])->name('admin.event-types.deactivate');
});
