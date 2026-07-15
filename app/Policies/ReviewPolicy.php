<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReviewPolicy
{
    public function moderate(User $user, Review $review): Response
    {
        if ($user->isSuperAdmin()) {
            return Response::deny('Encore support access is read-only.');
        }

        $review->loadMissing('performance.show');

        if ($review->performance?->show?->organisation_id !== $user->organisation_id) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
