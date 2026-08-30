<?php

namespace App\Application\Reviews;

use App\Domain\Invitations\InvitationToken;
use App\Models\Review;
use App\Models\Reviewer;
use App\Models\ReviewInvitation;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SubmitReviewService
{
    public function __construct(private readonly InvitationToken $tokens) {}

    /** @param array<string, mixed> $attributes
     * @return array{error?: string, review?: Review}
     */
    public function usingToken(string $token, array $attributes): array
    {
        $digests = $this->tokens->lookupDigests($token);

        return $this->submit(
            fn (): Builder => ReviewInvitation::query()->whereIn('token_hash', $digests),
            $attributes,
        );
    }

    /** @param array<string, mixed> $attributes
     * @return array{error?: string, review?: Review}
     */
    public function usingInvitationId(string $invitationId, array $attributes): array
    {
        return $this->submit(
            fn (): Builder => ReviewInvitation::query()->whereKey($invitationId),
            $attributes,
        );
    }

    /** @param Closure(): Builder<ReviewInvitation> $invitationQuery
     * @param  array<string, mixed>  $attributes
     * @return array{error?: string, review?: Review}
     */
    private function submit(Closure $invitationQuery, array $attributes): array
    {
        $emailHash = hash('sha256', Str::lower(trim($attributes['email'])));

        return DB::transaction(function () use ($invitationQuery, $emailHash, $attributes): array {
            $invitation = $invitationQuery()
                ->available()
                ->with('performance.show')
                ->lockForUpdate()
                ->first();

            if ($invitation === null) {
                return ['error' => 'invalid_token'];
            }

            if ($invitation->email_hash !== null && ! hash_equals($invitation->email_hash, $emailHash)) {
                return ['error' => 'email_mismatch'];
            }

            if ($invitation->performance?->show?->reviews_locked) {
                return ['error' => 'reviews_locked'];
            }

            $reviewer = Reviewer::firstOrCreate(
                ['email_hash' => $emailHash],
                ['display_name' => $attributes['display_name'] ?? null],
            );

            $review = Review::create([
                'performance_id' => $invitation->performance_id,
                'reviewer_id' => $reviewer->id,
                'rating' => $attributes['rating'],
                'would_recommend' => $attributes['would_recommend'],
                'tags' => $attributes['tags'] ?? null,
                'content' => $attributes['content'] ?? null,
                'verified' => true,
                'verification_source' => 'invitation',
                'moderation_status' => 'pending',
                'submitted_at' => now(),
            ]);

            $invitation->forceFill(['status' => 'used', 'used_at' => now()])->save();

            return ['review' => $review];
        });
    }
}
