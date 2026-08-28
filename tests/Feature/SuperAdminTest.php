<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_be_bootstrapped_from_console(): void
    {
        $this->artisan('encore:create-super-admin', [
            'email' => 'encore-admin@example.com',
            '--name' => 'Encore Admin',
        ])->expectsQuestion('Password (at least 10 characters)', 'secure-password')
            ->expectsQuestion('Confirm password', 'secure-password')
            ->expectsOutput('Encore super administrator created.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'encore-admin@example.com',
            'role' => 'super_admin',
            'organisation_id' => null,
        ]);
    }

    public function test_customer_cannot_access_encore_organisation_management(): void
    {
        $organisation = Organisation::create(['name' => 'Customer Organisation', 'is_active' => true]);
        $user = User::factory()->create(['organisation_id' => $organisation->id]);

        $this->actingAs($user)->get(route('super.organisations.index'))->assertForbidden();
    }

    public function test_super_admin_can_create_organisation_and_first_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->post(route('super.organisations.store'), [
            'name' => 'New Theatre Company',
            'support_email' => 'support@theatre.test',
            'notes' => 'Priority launch client.',
            'admin_name' => 'Client Manager',
            'admin_email' => 'manager@theatre.test',
            'admin_password' => 'secure-password',
            'admin_password_confirmation' => 'secure-password',
        ]);

        $organisation = Organisation::where('name', 'New Theatre Company')->firstOrFail();
        $response->assertRedirect(route('super.organisations.edit', $organisation));
        $this->assertDatabaseHas('users', [
            'organisation_id' => $organisation->id,
            'email' => 'manager@theatre.test',
            'role' => 'customer_admin',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('organisation_user_memberships', [
            'organisation_id' => $organisation->id,
            'user_id' => User::where('email', 'manager@theatre.test')->value('id'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $logs = AuditLog::query()->orderBy('created_at')->get();
        $this->assertCount(2, $logs);
        $this->assertSame($logs[0]->correlation_id, $logs[1]->correlation_id);
        $this->assertSame(['name', 'support_email', 'is_active', 'notes'], array_keys($logs[0]->after_state));
        $this->assertArrayNotHasKey('password', $logs[1]->after_state);
    }

    public function test_super_admin_can_manage_users_and_assign_shows(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $organisation = Organisation::create(['name' => 'Managed Organisation', 'is_active' => true]);
        $user = User::factory()->create(['organisation_id' => $organisation->id]);
        $show = $this->createShow('Assignable Show');

        $this->actingAs($superAdmin)->post(route('super.organisations.shows.store', $organisation), [
            'show_id' => $show->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('shows', ['id' => $show->id, 'organisation_id' => $organisation->id]);

        $this->actingAs($superAdmin)->patch(route('super.organisations.users.update', [$organisation, $user]), [
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => false,
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    public function test_support_view_is_scoped_to_selected_client_and_read_only(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $first = Organisation::create(['name' => 'First Organisation', 'is_active' => true]);
        $second = Organisation::create(['name' => 'Second Organisation', 'is_active' => true]);
        $firstShow = $this->createShow('First Client Show', $first);
        $this->createShow('Secret Second Show', $second);

        $response = $this->actingAs($superAdmin)->get(route('super.organisations.support', $first));

        $response->assertOk();
        $response->assertSee('Read-only support mode');
        $response->assertSee($firstShow->title);
        $response->assertDontSee('Secret Second Show');
        $response->assertDontSee('>Approve<', false);
        $response->assertDontSee('>Reject<', false);
        $this->assertDatabaseHas('audit_logs', [
            'organisation_id' => $first->id,
            'user_id' => $superAdmin->id,
            'action' => 'organisation.support_viewed',
        ]);
    }

    public function test_nested_resources_cannot_be_managed_through_another_organisation(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $first = Organisation::create(['name' => 'First Organisation', 'is_active' => true]);
        $second = Organisation::create(['name' => 'Second Organisation', 'is_active' => true]);
        $secondUser = User::factory()->create(['organisation_id' => $second->id]);
        $secondShow = $this->createShow('Second Organisation Show', $second);

        $this->actingAs($superAdmin)->patch(route('super.organisations.users.update', [$first, $secondUser]), [
            'name' => $secondUser->name,
            'email' => $secondUser->email,
            'is_active' => false,
        ])->assertForbidden();

        $this->actingAs($superAdmin)
            ->delete(route('super.organisations.shows.destroy', [$first, $secondShow]))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $secondUser->id, 'is_active' => true]);
        $this->assertDatabaseHas('shows', ['id' => $secondShow->id, 'organisation_id' => $second->id]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_audit_logs_are_immutable_through_the_model(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $organisation = Organisation::create(['name' => 'Audited Organisation', 'is_active' => true]);

        $this->actingAs($superAdmin)->get(route('super.organisations.support', $organisation))->assertOk();

        $auditLog = AuditLog::sole();

        $this->expectException(LogicException::class);
        $auditLog->update(['action' => 'tampered']);
    }

    private function createShow(string $title, ?Organisation $organisation = null): Show
    {
        $slug = str($title)->slug();

        return Show::create([
            'organisation_id' => $organisation?->id,
            'title' => $title,
            'slug' => $slug,
            'ticket_url' => 'https://tickets.example.com/'.$slug,
            'provider_source' => 'ticketpal',
            'provider_event_id' => $slug.'-event',
            'status' => 'upcoming',
        ]);
    }
}
