<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Create a new user.
     *
     * `role` and `active` are set via direct property assignment rather
     * than User::create()/fill() — they're intentionally excluded from
     * $fillable, so mass assignment would silently drop them instead of
     * applying the admin's chosen role.
     */
    public function create(array $data): User
    {
        $user = new User([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->role = UserRole::from($data['role']);
        $user->active = true;
        $user->save();

        return $user;
    }

    /**
     * Update an existing user's name, email, role, and/or password.
     *
     * @throws ValidationException if this would demote the last active admin
     */
    public function update(User $user, array $data): User
    {
        if (array_key_exists('role', $data)) {
            $newRole = UserRole::from($data['role']);

            if ($user->role === UserRole::Admin
                && $newRole !== UserRole::Admin
                && $this->wouldRemoveLastActiveAdmin($user)) {
                throw ValidationException::withMessages([
                    'role' => 'Cannot change the role of the last active administrator.',
                ]);
            }

            $user->role = $newRole;
        }

        if (array_key_exists('name', $data)) {
            $user->name = $data['name'];
        }

        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }

        if (! empty($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $user;
    }

    /**
     * Activate or deactivate a user.
     *
     * @throws ValidationException if this would deactivate the last active admin
     */
    public function setActive(User $user, bool $active): User
    {
        if (! $active
            && $user->role === UserRole::Admin
            && $this->wouldRemoveLastActiveAdmin($user)) {
            throw ValidationException::withMessages([
                'active' => 'Cannot deactivate the last active administrator.',
            ]);
        }

        $user->active = $active;
        $user->save();

        return $user;
    }

    /**
     * Determine whether removing this user's active-admin status (via role
     * change, deactivation, or deletion) would leave the system with zero
     * active administrators.
     */
    private function wouldRemoveLastActiveAdmin(User $user): bool
    {
        if (! $user->active) {
            return false;
        }

        return User::query()
            ->where('role', UserRole::Admin)
            ->where('active', true)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}
