<?php

namespace App\Policies;

use App\Models\Organisation;
use App\Models\Show;
use App\Models\User;

class ShowPolicy
{
    public function createManual(User $user): bool
    {
        return ! $user->isSuperAdmin() && $user->organisation_id !== null;
    }

    public function manageManual(User $user, Show $show): bool
    {
        return ! $user->isSuperAdmin()
            && $user->organisation_id === $show->organisation_id
            && $show->provider_source === Show::SOURCE_MANUAL;
    }

    public function assign(User $user, Show $show, Organisation $organisation): bool
    {
        return $user->isSuperAdmin() && $show->organisation_id === null;
    }

    public function unassign(User $user, Show $show, Organisation $organisation): bool
    {
        return $user->isSuperAdmin() && $show->organisation_id === $organisation->id;
    }
}
