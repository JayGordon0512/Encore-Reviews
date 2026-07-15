<?php

namespace App\Policies;

use App\Models\Organisation;
use App\Models\Show;
use App\Models\User;

class ShowPolicy
{
    public function assign(User $user, Show $show, Organisation $organisation): bool
    {
        return $user->isSuperAdmin() && $show->organisation_id === null;
    }

    public function unassign(User $user, Show $show, Organisation $organisation): bool
    {
        return $user->isSuperAdmin() && $show->organisation_id === $organisation->id;
    }
}
