<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\OrganisationUserMembership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredOrganiserController extends Controller
{
    public function create(): View
    {
        return view('auth.organiser-register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'organisation_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
            'authority_confirmed' => ['accepted'],
        ]);

        $email = $validated['email'];
        $correlationId = (string) Str::uuid();

        DB::transaction(function () use ($request, $validated, $email, $correlationId): void {
            $organisation = Organisation::create([
                'name' => trim($validated['organisation_name']),
                'support_email' => $email,
                'is_active' => false,
                'lifecycle_status' => 'pending_review',
            ]);
            $user = User::create([
                'organisation_id' => $organisation->id,
                'name' => trim($validated['name']),
                'email' => $email,
                'password' => $validated['password'],
                'role' => 'customer_admin',
                'is_active' => false,
            ]);
            OrganisationUserMembership::create([
                'organisation_id' => $organisation->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'is_active' => false,
            ]);
            AuditLog::create([
                'organisation_id' => $organisation->id,
                'user_id' => null,
                'action' => 'organisation.registration_submitted',
                'entity_type' => $organisation->getMorphClass(),
                'entity_id' => $organisation->id,
                'after_state' => [
                    'name' => $organisation->name,
                    'support_email' => $organisation->support_email,
                    'lifecycle_status' => $organisation->lifecycle_status,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);
        });

        return redirect()->route('organisers.create')->with(
            'status',
            'Your organiser account has been created and is awaiting verification. We will contact you when access is ready.',
        );
    }
}
