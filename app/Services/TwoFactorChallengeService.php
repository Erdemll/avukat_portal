<?php

namespace App\Services;

use App\Models\TwoFactorChallenge;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeService
{
    public function issue(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->twoFactorChallenges()
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            $code = (string) random_int(100000, 999999);
            $challenge = new TwoFactorChallenge([
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
            ]);
            $challenge->user_id = $user->id;
            $challenge->save();

            $user->notify(new TwoFactorCodeNotification($code));
        });
    }

    public function verify(User $user, string $code): void
    {
        $error = DB::transaction(function () use ($user, $code): ?string {
            $challenge = TwoFactorChallenge::query()
                ->where('user_id', $user->id)
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($challenge === null || $challenge->expires_at->isPast()) {
                return 'Doğrulama kodunun süresi doldu. Yeni kod isteyin.';
            }

            if ($challenge->attempts >= 5) {
                return 'Deneme sınırı aşıldı. Yeni kod isteyin.';
            }

            if (! Hash::check($code, $challenge->code_hash)) {
                $challenge->increment('attempts');

                return 'Doğrulama kodu hatalı.';
            }

            $challenge->forceFill(['consumed_at' => now()])->save();

            return null;
        });

        if ($error !== null) {
            throw ValidationException::withMessages(['code' => $error]);
        }
    }
}
