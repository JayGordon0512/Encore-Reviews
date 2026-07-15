<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\Show;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganisationController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Organisation::class);

        $organisations = Organisation::query()
            ->withCount(['users', 'shows'])
            ->orderBy('name')
            ->get();

        return view('admin.organisations.index', compact('organisations'));
    }

    public function create(): View
    {
        Gate::authorize('create', Organisation::class);

        return view('admin.organisations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Organisation::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $correlationId = (string) Str::uuid();
        $organisation = DB::transaction(function () use ($request, $validated, $correlationId): Organisation {
            $organisation = Organisation::create([
                'name' => $validated['name'],
                'support_email' => $validated['support_email'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
            ]);

            $user = $organisation->users()->create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => $validated['admin_password'],
                'role' => 'customer_admin',
                'is_active' => true,
            ]);

            $this->recordAudit(
                $request,
                'organisation.created',
                $organisation,
                $organisation->id,
                null,
                $this->auditLogger->snapshot($organisation, ['name', 'support_email', 'is_active', 'notes']),
                $correlationId
            );
            $this->recordAudit(
                $request,
                'organisation.user_created',
                $user,
                $organisation->id,
                null,
                $this->auditLogger->snapshot($user, ['organisation_id', 'name', 'email', 'role', 'is_active']),
                $correlationId
            );

            return $organisation;
        });

        return redirect()->route('super.organisations.edit', $organisation)
            ->with('status', 'Organisation and first administrator created.');
    }

    public function edit(Organisation $organisation): View
    {
        Gate::authorize('view', $organisation);

        $organisation->load(['users' => fn ($query) => $query->orderBy('name'), 'shows' => fn ($query) => $query->orderBy('title')]);
        $availableShows = Show::query()->whereNull('organisation_id')->orderBy('title')->get();

        return view('admin.organisations.edit', compact('organisation', 'availableShows'));
    }

    public function update(Request $request, Organisation $organisation): RedirectResponse
    {
        Gate::authorize('update', $organisation);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $fields = ['name', 'support_email', 'is_active', 'notes'];
        $before = $this->auditLogger->snapshot($organisation, $fields);

        DB::transaction(function () use ($request, $organisation, $validated, $fields, $before): void {
            $organisation->update($validated);
            $this->recordAudit(
                $request,
                'organisation.updated',
                $organisation,
                $organisation->id,
                $before,
                $this->auditLogger->snapshot($organisation, $fields)
            );
        });

        return back()->with('status', 'Organisation updated.');
    }

    public function support(Request $request, Organisation $organisation, DashboardController $dashboard): View
    {
        Gate::authorize('support', $organisation);
        $this->recordAudit(
            $request,
            'organisation.support_viewed',
            $organisation,
            $organisation->id,
            null,
            ['support_mode' => 'read_only']
        );

        return $dashboard->forOrganisation($organisation, true);
    }

    public function storeUser(Request $request, Organisation $organisation): RedirectResponse
    {
        Gate::authorize('createUser', $organisation);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        DB::transaction(function () use ($request, $organisation, $validated): void {
            $user = $organisation->users()->create($validated + [
                'role' => 'customer_admin',
                'is_active' => true,
            ]);
            $this->recordAudit(
                $request,
                'organisation.user_created',
                $user,
                $organisation->id,
                null,
                $this->auditLogger->snapshot($user, ['organisation_id', 'name', 'email', 'role', 'is_active'])
            );
        });

        return back()->with('status', 'Organisation user created.');
    }

    public function updateUser(Request $request, Organisation $organisation, User $user): RedirectResponse
    {
        Gate::authorize('manageUser', [$organisation, $user]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ]);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $fields = ['organisation_id', 'name', 'email', 'role', 'is_active'];
        $before = $this->auditLogger->snapshot($user, $fields);

        DB::transaction(function () use ($request, $organisation, $user, $validated, $fields, $before): void {
            $user->update($validated);
            $this->recordAudit(
                $request,
                'organisation.user_updated',
                $user,
                $organisation->id,
                $before,
                $this->auditLogger->snapshot($user, $fields)
            );
        });

        return back()->with('status', 'Organisation user updated.');
    }

    public function assignShow(Request $request, Organisation $organisation): RedirectResponse
    {
        $validated = $request->validate([
            'show_id' => ['required', 'uuid', Rule::exists('shows', 'id')->whereNull('organisation_id')],
        ]);

        $show = Show::query()->findOrFail($validated['show_id']);
        Gate::authorize('assign', [$show, $organisation]);
        $before = $this->auditLogger->snapshot($show, ['organisation_id']);

        DB::transaction(function () use ($request, $organisation, $show, $before): void {
            $show->update(['organisation_id' => $organisation->id]);
            $this->recordAudit(
                $request,
                'organisation.show_assigned',
                $show,
                $organisation->id,
                $before,
                $this->auditLogger->snapshot($show, ['organisation_id'])
            );
        });

        return back()->with('status', 'Show assigned to organisation.');
    }

    public function unassignShow(Request $request, Organisation $organisation, Show $show): RedirectResponse
    {
        Gate::authorize('unassign', [$show, $organisation]);
        $before = $this->auditLogger->snapshot($show, ['organisation_id']);

        DB::transaction(function () use ($request, $organisation, $show, $before): void {
            $show->update(['organisation_id' => null]);
            $this->recordAudit(
                $request,
                'organisation.show_unassigned',
                $show,
                $organisation->id,
                $before,
                $this->auditLogger->snapshot($show, ['organisation_id'])
            );
        });

        return back()->with('status', 'Show removed from organisation.');
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function recordAudit(
        Request $request,
        string $action,
        Model $entity,
        ?string $organisationId,
        ?array $before,
        ?array $after,
        ?string $correlationId = null
    ): void {
        $this->auditLogger->record(
            $request->user(),
            $action,
            $entity,
            $organisationId,
            $before,
            $after,
            $request->ip(),
            $request->userAgent(),
            $correlationId ?? (string) Str::uuid()
        );
    }
}
