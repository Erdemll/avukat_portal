<?php

namespace App\Auth;

use App\Models\Role;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class SicilNoUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) || (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return null;
        }

        $query = $this->createModel()->newQuery();

        if (isset($credentials['sicil_no'])) {
            $lawyerRoleId = Role::query()->where('slug', 'lawyer')->value('id');
            $query->where('tc_kimlik_no', $credentials['sicil_no'])
                ->where('role_id', $lawyerRoleId)
                ->where('is_active', true);
        } else {
            foreach ($credentials as $key => $value) {
                if (! str_contains($key, 'password')) {
                    $query->where($key, $value);
                }
            }
        }

        return $query->first();
    }
}
