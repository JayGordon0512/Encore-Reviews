<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\Show;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganisationController extends Controller
{
    public function index(): View
    {
        $organisations = Organisation::query()
            ->withCount(['users', 'shows'])
            ->orderBy('name')
            ->get();

        return view('admin.organisations.index', compact('organisations'));
    }

    public function create(): View
    {
        return view('admin.organisations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $organisation = DB::transaction(function () use ($validated): Organisation {
            $organisation = Organisation::create([
                'name' => $validated['name'],
                'support_email' => $validated['support_email'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
            ]);

            $organisation->users()->create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => $validated['admin_password'],
                'role' => 'customer_admin',
                'is_active' => true,
            ]);

            return $organisation;
        });

        return redirect()->route('super.organisations.edit', $organisation)
            ->with('status', 'Organisation and first administrator created.');
    }

    public function edit(Organisation $organisation): View
    {
        $organisation->load(['users' => fn ($query) => $query->orderBy('name'), 'shows' => fn ($query) => $query->orderBy('title')]);
        $availableShows = Show::query()->whereNull('organisation_id')->orderBy('title')->get();

        return view('admin.organisations.edit', compact('organisation', 'availableShows'));
    }

    public function update(Request $request, Organisation $organisation): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $organisation->update($validated);

        return back()->with('status', 'Organisation updated.');
    }

    public function support(Organisation $organisation, DashboardController $dashboard): View
    {
        return $dashboard->forOrganisation($organisation, true);
    }

    public function storeUser(Request $request, Organisation $organisation): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $organisation->users()->create($validated + [
            'role' => 'customer_admin',
            'is_active' => true,
        ]);

        return back()->with('status', 'Organisation user created.');
    }

    public function updateUser(Request $request, Organisation $organisation, User $user): RedirectResponse
    {
        abort_unless($user->organisation_id === $organisation->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ]);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('status', 'Organisation user updated.');
    }

    public function assignShow(Request $request, Organisation $organisation): RedirectResponse
    {
        $validated = $request->validate([
            'show_id' => ['required', 'uuid', Rule::exists('shows', 'id')->whereNull('organisation_id')],
        ]);

        Show::query()->whereKey($validated['show_id'])->whereNull('organisation_id')->update([
            'organisation_id' => $organisation->id,
        ]);

        return back()->with('status', 'Show assigned to organisation.');
    }

    public function unassignShow(Organisation $organisation, Show $show): RedirectResponse
    {
        abort_unless($show->organisation_id === $organisation->id, 404);

        $show->update(['organisation_id' => null]);

        return back()->with('status', 'Show removed from organisation.');
    }
}
