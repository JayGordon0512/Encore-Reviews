<?php

namespace App\Policies;

use App\Models\Organisation;
use App\Models\User;

class OrganisationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Organisation $organisation): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Organisation $organisation): bool
    {
        return $user->isSuperAdmin();
    }

    public function support(User $user, Organisation $organisation): bool
    {
        return $user->isSuperAdmin();
    }

    public function viewDashboard(User $user, Organisation $organisation): bool
    {
        return $user->isSuperAdmin() || $user->organisation_id === $organisation->id;
    }

    public function createUser(User $user, Organisation $organisation): bool
    {
        return $user->isSuperAdmin();
    }

    public function manageUser(User $user, Organisation $organisation, User $managedUser): bool
    {
        return $user->isSuperAdmin() && $managedUser->organisation_id === $organisation->id;
    }
}
