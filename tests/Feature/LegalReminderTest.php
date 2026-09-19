<?php

use App\Models\Deadline;
use App\Models\Hearing;
use App\Notifications\DeadlineReminderNotification;
use App\Notifications\HearingReminderNotification;
use Illuminate\Support\Facades\Notification;

it('sends each upcoming hearing and deadline reminder only once', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $hearing = Hearing::factory()->create(['case_file_id' => $caseFile, 'lawyer_id' => $lawyer, 'created_by' => $manager, 'hearing_at' => now()->addDay()]);
    $deadline = Deadline::factory()->create(['case_file_id' => $caseFile, 'assigned_lawyer_id' => $lawyer, 'created_by' => $manager, 'due_at' => now()->addDay()]);

    $this->artisan('legal:send-reminders')->assertSuccessful();
    Notification::assertSentTo($lawyer, HearingReminderNotification::class);
    Notification::assertSentTo($lawyer, DeadlineReminderNotification::class);
    expect($hearing->fresh()->reminder_sent_at)->not->toBeNull()
        ->and($deadline->fresh()->reminder_sent_at)->not->toBeNull();

    $this->artisan('legal:send-reminders')->assertSuccessful();
    Notification::assertSentToTimes($lawyer, HearingReminderNotification::class, 1);
    Notification::assertSentToTimes($lawyer, DeadlineReminderNotification::class, 1);
});

it('does not remind cancelled or distant legal operations', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    Hearing::factory()->create(['case_file_id' => $caseFile, 'lawyer_id' => $lawyer, 'created_by' => $manager, 'hearing_at' => now()->addDays(10)]);
    Deadline::factory()->create(['case_file_id' => $caseFile, 'assigned_lawyer_id' => $lawyer, 'created_by' => $manager, 'due_at' => now()->addDay(), 'status' => 'cancelled']);

    $this->artisan('legal:send-reminders')->assertSuccessful();
    Notification::assertNothingSent();
});
