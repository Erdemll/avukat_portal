<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function findOrCreateDirect(User $firstLawyer, User $secondLawyer): Conversation
    {
        $directKey = collect([$firstLawyer->getKey(), $secondLawyer->getKey()])
            ->sort()
            ->implode(':');

        return DB::transaction(function () use ($directKey, $firstLawyer, $secondLawyer): Conversation {
            $conversation = Conversation::query()->firstOrCreate([
                'direct_key' => $directKey,
            ], [
                'type' => 'direct',
            ]);

            foreach ([$firstLawyer, $secondLawyer] as $lawyer) {
                ConversationParticipant::query()->firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'user_id' => $lawyer->id,
                ], [
                    'joined_at' => now(),
                ]);
            }

            return $conversation->load('participants:id,name,is_active');
        }, attempts: 3);
    }
}
