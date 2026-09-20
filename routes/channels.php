<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId): bool {
    return $user->isLawyer() && Conversation::query()
        ->whereKey($conversationId)
        ->whereHas('participants', fn ($query) => $query->whereKey($user->id))
        ->exists();
});

Broadcast::channel('user.{userId}', fn (User $user, int $userId): bool => $user->isLawyer() && $user->id === $userId);
