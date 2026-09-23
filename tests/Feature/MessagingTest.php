<?php

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageReceivedNotification;
use App\Services\Messaging\ConversationService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

function messagingConversation(User $firstLawyer, User $secondLawyer): Conversation
{
    return app(ConversationService::class)->findOrCreateDirect($firstLawyer, $secondLawyer);
}

it('requires authentication for the messages page', function () {
    $this->get(route('messages.index'))->assertRedirect(route('login'));
});

it('forbids non lawyer roles from the messages page', function (string $role) {
    $this->actingAs(userWithRole($role))->get(route('messages.index'))->assertForbidden();
})->with(['employee', 'manager']);

it('shows active lawyers but excludes the current and inactive lawyers', function () {
    $lawyer = userWithRole('lawyer');
    $visibleLawyer = userWithRole('lawyer');
    $inactiveLawyer = userWithRole('lawyer', false);
    $employee = userWithRole('employee');

    $this->actingAs($lawyer)->get(route('messages.index'))
        ->assertOk()->assertSee('data-lawyer-id="'.$visibleLawyer->id.'"', false)
        ->assertDontSee('data-lawyer-id="'.$lawyer->id.'"', false)
        ->assertDontSee('data-lawyer-id="'.$inactiveLawyer->id.'"', false)
        ->assertDontSee('data-lawyer-id="'.$employee->id.'"', false)->assertSee('Mesajlar');
});

it('renders lawyer rows as links and selects a lawyer from the query string', function () {
    $lawyer = userWithRole('lawyer');
    $targetLawyer = userWithRole('lawyer');

    $this->actingAs($lawyer)->get(route('messages.index', ['lawyer' => $targetLawyer->id]))
        ->assertSee('href="'.route('messages.index', ['lawyer' => $targetLawyer->id]).'"', false)
        ->assertSee('"initial_lawyer_id":'.$targetLawyer->id, false);
});

it('creates one direct conversation for the same two lawyers', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');

    $firstResponse = $this->actingAs($firstLawyer)->postJson(route('messages.conversations.store'), ['user_id' => $secondLawyer->id])->assertOk();
    $secondResponse = $this->actingAs($secondLawyer)->postJson(route('messages.conversations.store'), ['user_id' => $firstLawyer->id])->assertOk();

    expect($firstResponse->json('id'))->toBe($secondResponse->json('id'));
    expect(Conversation::query()->count())->toBe(1);
    expect(ConversationParticipant::query()->count())->toBe(2);
    $this->assertDatabaseHas('conversations', [
        'id' => $firstResponse->json('id'),
        'type' => 'direct',
        'direct_key' => collect([$firstLawyer->id, $secondLawyer->id])->sort()->implode(':'),
    ]);
});

it('rejects starting a conversation with oneself', function () {
    $lawyer = userWithRole('lawyer');

    $this->actingAs($lawyer)->postJson(route('messages.conversations.store'), ['user_id' => $lawyer->id])
        ->assertUnprocessable()->assertJsonValidationErrors('user_id');

    expect(Conversation::query()->count())->toBe(0);
});

it('rejects inactive and non lawyer conversation targets', function (string $role, bool $active) {
    $lawyer = userWithRole('lawyer');
    $target = userWithRole($role, $active);

    $this->actingAs($lawyer)->postJson(route('messages.conversations.store'), ['user_id' => $target->id])
        ->assertUnprocessable()->assertJsonValidationErrors('user_id');

    expect(Conversation::query()->count())->toBe(0);
})->with([
    'employee' => ['employee', true],
    'manager' => ['manager', true],
    'inactive lawyer' => ['lawyer', false],
]);

it('allows participants to open a conversation and returns messages oldest first', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    Message::factory()->count(55)->for($conversation)->create(['sender_id' => $firstLawyer]);

    $response = $this->actingAs($secondLawyer)->getJson(route('messages.conversations.show', $conversation))
        ->assertOk()->assertJsonCount(50, 'messages')->assertJsonPath('pagination.has_more', true);

    $ids = collect($response->json('messages'))->pluck('id');
    expect($ids->all())->toBe($ids->sort()->values()->all());
    expect($response->json('pagination.next_before'))->toBe($ids->first());
});

it('forbids a non participant from opening another lawyers conversation', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $outsider = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);

    $this->actingAs($outsider)->getJson(route('messages.conversations.show', $conversation))->assertForbidden();
});

it('stores a plain text message using the authenticated sender and dispatches its event', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    Event::fake([MessageSent::class]);
    Notification::fake([MessageReceivedNotification::class]);

    $this->actingAs($firstLawyer)->postJson(route('messages.store', $conversation), [
        'body' => '  Merhaba, dosyayı inceledim.  ',
        'sender_id' => $secondLawyer->id,
        'conversation_id' => 999,
    ])->assertCreated()->assertJsonPath('message.body', 'Merhaba, dosyayı inceledim.')
        ->assertJsonPath('message.sender_id', $firstLawyer->id)
        ->assertJsonPath('message.conversation_id', $conversation->id);

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'sender_id' => $firstLawyer->id,
        'body' => 'Merhaba, dosyayı inceledim.',
    ]);
    Event::assertDispatched(MessageSent::class, fn (MessageSent $event): bool => $event->message->sender_id === $firstLawyer->id);
    Notification::assertSentTo($secondLawyer, MessageReceivedNotification::class);
    Notification::assertNotSentTo($firstLawyer, MessageReceivedNotification::class);
});

it('stores a body free application notification for the recipient', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    Event::fake([MessageSent::class]);

    $this->actingAs($firstLawyer)->postJson(route('messages.store', $conversation), [
        'body' => 'Bu içerik bildirime taşınmamalı.',
    ])->assertCreated();

    $notification = $secondLawyer->notifications()->firstOrFail();
    expect($notification->data)->toMatchArray([
        'type' => 'message_received',
        'conversation_id' => $conversation->id,
        'sender_id' => $firstLawyer->id,
        'message' => 'Av. '.$firstLawyer->name.' size yeni bir mesaj gönderdi.',
    ])->not->toHaveKey('body')
        ->and($notification->data['url'])->toBe(route('messages.index', ['conversation' => $conversation->id]))
        ->and($firstLawyer->notifications()->count())->toBe(0);
});

it('opens message notifications in the related conversation', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    Event::fake([MessageSent::class]);

    $this->actingAs($firstLawyer)->postJson(route('messages.store', $conversation), ['body' => 'Yeni mesaj'])->assertCreated();
    $notification = $secondLawyer->notifications()->firstOrFail();

    $this->actingAs($secondLawyer)->get(route('notifications.open', $notification))
        ->assertRedirect(route('messages.index', ['conversation' => $conversation->id]));
    expect($notification->fresh()->read_at)->not->toBeNull();
    $this->actingAs($secondLawyer)->get(route('messages.index', ['conversation' => $conversation->id]))
        ->assertOk()
        ->assertSee('"initial_conversation_id":'.$conversation->id, false);
});

it('rejects invalid message bodies', function (mixed $body) {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);

    $this->actingAs($firstLawyer)->postJson(route('messages.store', $conversation), ['body' => $body])
        ->assertUnprocessable()->assertJsonValidationErrors('body');

    expect(Message::query()->count())->toBe(0);
})->with([
    'empty' => '',
    'whitespace only' => " \n\t ",
    'over 5000 characters' => str_repeat('a', 5001),
]);

it('forbids non participants and non lawyer roles from sending messages', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);

    foreach (['lawyer', 'manager', 'employee'] as $role) {
        $this->actingAs(userWithRole($role))->postJson(route('messages.store', $conversation), ['body' => 'Yetkisiz mesaj'])->assertForbidden();
    }

    expect(Message::query()->count())->toBe(0);
});

it('uses an explicit private broadcast payload without sensitive user data', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    $message = Message::factory()->for($conversation)->create(['sender_id' => $firstLawyer, 'body' => 'Gizli görüşme metni'])->load('sender');
    $event = new MessageSent($message, [$firstLawyer->id, $secondLawyer->id]);

    expect($event)->toBeInstanceOf(ShouldBroadcastNow::class)
        ->and($event->broadcastAs())->toBe('message.sent')
        ->and(array_map('strval', $event->broadcastOn()))->toBe([
            'private-conversation.'.$conversation->id,
            'private-user.'.$firstLawyer->id,
            'private-user.'.$secondLawyer->id,
        ])
        ->and($event->broadcastWith())->toHaveKeys(['message'])
        ->and($event->broadcastWith()['message'])->toHaveKeys(['id', 'conversation_id', 'sender_id', 'sender_name', 'body', 'created_at'])
        ->not->toHaveKeys(['email', 'role', 'password', 'tc_kimlik_no'])
        ->and($event->broadcastWith()['message']['body'])->toBe('Gizli görüşme metni');
});

it('authorizes private conversation channels only for participants', function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-app');
    require base_path('routes/channels.php');
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $outsider = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    $payload = ['socket_id' => '1234.5678', 'channel_name' => 'private-conversation.'.$conversation->id];

    $this->actingAs($firstLawyer)->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-user.'.$firstLawyer->id,
    ])->assertOk();
    $this->actingAs($firstLawyer)->postJson('/broadcasting/auth', $payload)->assertOk();
    $this->actingAs($outsider)->postJson('/broadcasting/auth', $payload)->assertForbidden();
    auth()->logout();
    expect($this->postJson('/broadcasting/auth', $payload)->status())->toBeIn([401, 403]);
});

it('updates the read timestamp and excludes own messages from unread counts', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    Message::factory()->for($conversation)->count(2)->create(['sender_id' => $firstLawyer]);
    Message::factory()->for($conversation)->create(['sender_id' => $secondLawyer]);
    $secondLawyer->notify(new MessageReceivedNotification($conversation->id, $firstLawyer->id, $firstLawyer->name));
    $secondLawyer->notify(new MessageReceivedNotification($conversation->id, $firstLawyer->id, $firstLawyer->name));

    $this->actingAs($secondLawyer)->get(route('messages.index'))->assertOk()->assertSee('2 okunmamış mesaj');
    $this->actingAs($secondLawyer)->postJson(route('messages.read', $conversation))
        ->assertOk()
        ->assertJsonPath('notifications_read', 2);

    expect(ConversationParticipant::query()->where('conversation_id', $conversation->id)
        ->where('user_id', $secondLawyer->id)->value('last_read_at'))->not->toBeNull()
        ->and($secondLawyer->unreadNotifications()->count())->toBe(0);
    $this->actingAs($secondLawyer)->get(route('messages.index'))->assertOk()->assertDontSee('2 okunmamış mesaj');
});

it('forbids a non participant from marking a conversation as read', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $outsider = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);

    $this->actingAs($outsider)->postJson(route('messages.read', $conversation))->assertForbidden();
});

it('escapes message HTML in the sidebar', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    Message::factory()->for($conversation)->create(['sender_id' => $firstLawyer, 'body' => '<script>alert("xss")</script>']);

    $this->actingAs($secondLawyer)->get(route('messages.index'))->assertOk()
        ->assertSee('&lt;script&gt;alert', false)->assertDontSee('<script>alert("xss")</script>', false);
});

it('keeps csrf protection on conversation writes', function () {
    $route = app(Router::class)->getRoutes()->getByName('messages.conversations.store');

    expect($route->gatherMiddleware())->toContain('web');
});

it('rate limits message sending per authenticated lawyer', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    Event::fake([MessageSent::class]);

    foreach (range(1, 60) as $number) {
        $this->actingAs($firstLawyer)->postJson(route('messages.store', $conversation), ['body' => 'Mesaj '.$number])->assertCreated();
    }

    $this->actingAs($firstLawyer)->postJson(route('messages.store', $conversation), ['body' => 'Sınırı aşan mesaj'])->assertTooManyRequests();
    expect(Message::query()->count())->toBe(60);
});

it('forbids manager and employee users from messaging api endpoints', function (string $role) {
    $target = userWithRole('lawyer');

    $this->actingAs(userWithRole($role))->postJson(route('messages.conversations.store'), ['user_id' => $target->id])->assertForbidden();
})->with(['manager', 'employee']);

it('preserves historical conversations when the other lawyer is deactivated', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    Message::factory()->for($conversation)->create(['sender_id' => $secondLawyer, 'body' => 'Arşiv mesajı']);
    $secondLawyer->update(['is_active' => false]);

    $this->actingAs($firstLawyer)->getJson(route('messages.conversations.show', $conversation))->assertOk()
        ->assertJsonPath('conversation.participant.is_active', false)->assertJsonPath('messages.0.body', 'Arşiv mesajı');
    $this->actingAs($firstLawyer)->get(route('messages.index', ['conversation' => $conversation->id]))
        ->assertSee('Geçmiş görüşmeler')->assertSee('Arşiv mesajı');
});

it('returns 403 and keeps the history unchanged when messaging an inactive conversation participant', function () {
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $conversation = messagingConversation($firstLawyer, $secondLawyer);
    $historicMessage = Message::factory()->for($conversation)->create(['sender_id' => $secondLawyer]);
    $secondLawyer->forceFill(['is_active' => false])->save();
    Notification::fake([MessageReceivedNotification::class]);

    $this->actingAs($firstLawyer)->postJson(route('messages.store', $conversation), ['body' => 'Yeni mesaj'])->assertForbidden();

    expect($conversation->messages()->pluck('id')->all())->toBe([$historicMessage->id]);
    Notification::assertNotSentTo($secondLawyer, MessageReceivedNotification::class);
});
