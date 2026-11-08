<?php

namespace Tests\Feature;

use App\Models\CompanyActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyUserManagementAndActivityLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_users_list_and_search(): void
    {
        $owner = User::create(['name' => 'Boss Owner', 'email' => 'boss@example.test', 'password' => 'password', 'role' => 'owner', 'is_active' => true]);
        $admin = User::create(['name' => 'Staff Admin', 'email' => 'staff@example.test', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($owner)
            ->get(route('company.users.index'))
            ->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertSee('Boss Owner')
            ->assertSee('Staff Admin');

        // Search test: viewData contains only Staff Admin
        $res = $this->actingAs($owner)
            ->get(route('company.users.index', ['search' => 'Staff']))
            ->assertOk();
        $this->assertCount(1, $res->viewData('users'));
        $this->assertSame('Staff Admin', $res->viewData('users')->first()->name);
    }

    public function test_admin_is_strictly_forbidden_from_user_management(): void
    {
        $admin = User::create(['name' => 'Staff Admin', 'email' => 'staff@example.test', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);
        $target = User::create(['name' => 'Target User', 'email' => 'target@example.test', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get(route('company.users.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('company.users.store'), ['name' => 'New', 'email' => 'new@test.com', 'role' => 'admin', 'password' => '12345678'])->assertForbidden();
        $this->actingAs($admin)->put(route('company.users.update', $target), ['name' => 'New Name', 'email' => $target->email])->assertForbidden();
        $this->actingAs($admin)->post(route('company.users.role', $target), ['role' => 'owner'])->assertForbidden();
        $this->actingAs($admin)->post(route('company.users.toggle-status', $target))->assertForbidden();
        $this->actingAs($admin)->post(route('company.users.reset-password', $target), ['password' => 'newpassword123', 'password_confirmation' => 'newpassword123'])->assertForbidden();
    }

    public function test_owner_can_create_user_and_logs_activity(): void
    {
        $owner = User::create(['name' => 'Boss Owner', 'email' => 'boss@example.test', 'password' => 'password', 'role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->post(route('company.users.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'role' => 'admin',
            'whatsapp' => '081234567890',
            'password' => 'secret12345',
        ]);

        $response->assertRedirect(route('company.users.index'));

        $newUser = User::where('email', 'budi@example.test')->firstOrFail();
        $this->assertSame('Budi Santoso', $newUser->name);
        $this->assertSame('admin', $newUser->role);
        $this->assertTrue($newUser->isActive());
        $this->assertTrue(Hash::check('secret12345', $newUser->password));

        // Activity log recorded
        $log = CompanyActivityLog::where('action', 'user_created')->latest('id')->firstOrFail();
        $this->assertSame($owner->id, $log->user_id);
        $this->assertStringContainsString('Budi Santoso', $log->description);

        // Sensitive password is NEVER in properties
        $this->assertStringNotContainsString('secret12345', json_encode($log->properties));
    }

    public function test_owner_can_update_user_and_change_role(): void
    {
        $owner = User::create(['name' => 'Boss Owner', 'email' => 'boss@example.test', 'password' => 'password', 'role' => 'owner', 'is_active' => true]);
        $user = User::create(['name' => 'Anisa', 'email' => 'anisa@example.test', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);

        // Update info
        $this->actingAs($owner)->put(route('company.users.update', $user), [
            'name' => 'Anisa Rahmawati',
            'email' => 'anisa_new@example.test',
            'whatsapp' => '089988776655',
        ])->assertRedirect(route('company.users.index'));

        $user->refresh();
        $this->assertSame('Anisa Rahmawati', $user->name);
        $this->assertSame('anisa_new@example.test', $user->email);

        // Change role to owner
        $this->actingAs($owner)->post(route('company.users.role', $user), [
            'role' => 'owner',
        ])->assertRedirect(route('company.users.index'));

        $user->refresh();
        $this->assertTrue($user->isOwner());

        $this->assertDatabaseHas('company_activity_logs', [
            'action' => 'role_changed',
            'resource_id' => (string) $user->id,
        ]);
    }

    public function test_last_active_owner_protection_prevents_demotion_and_deactivation(): void
    {
        // Only one active owner exists
        $soleOwner = User::create(['name' => 'Sole Owner', 'email' => 'sole@example.test', 'password' => 'password', 'role' => 'owner', 'is_active' => true]);

        // Attempting to demote sole owner to admin is blocked with 422
        $this->actingAs($soleOwner)->post(route('company.users.role', $soleOwner), [
            'role' => 'admin',
        ])->assertStatus(422);

        $soleOwner->refresh();
        $this->assertTrue($soleOwner->isOwner());

        // Self deactivation is blocked with 422
        $this->actingAs($soleOwner)->post(route('company.users.toggle-status', $soleOwner))
            ->assertStatus(422);

        $this->assertTrue($soleOwner->isActive());
    }

    public function test_owner_can_toggle_user_status_and_deactivated_user_cannot_login(): void
    {
        $owner = User::create(['name' => 'Boss Owner', 'email' => 'boss@example.test', 'password' => 'password', 'role' => 'owner', 'is_active' => true]);
        $employee = User::create(['name' => 'Emp User', 'email' => 'emp@example.test', 'password' => Hash::make('mypassword'), 'role' => 'admin', 'is_active' => true]);

        // Deactivate
        $this->actingAs($owner)->post(route('company.users.toggle-status', $employee))
            ->assertRedirect(route('company.users.index'));

        $employee->refresh();
        $this->assertFalse($employee->isActive());

        // Log out owner before attempting login
        $this->post(route('logout'));

        // Attempt login as deactivated user fails
        $this->post(route('login'), [
            'email' => 'emp@example.test',
            'password' => 'mypassword',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        // Reactivate
        $this->actingAs($owner)->post(route('company.users.toggle-status', $employee))
            ->assertRedirect(route('company.users.index'));

        $employee->refresh();
        $this->assertTrue($employee->isActive());

        // Log out owner before attempting login
        $this->post(route('logout'));

        // Login succeeds now
        $this->post(route('login'), [
            'email' => 'emp@example.test',
            'password' => 'mypassword',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($employee);
    }

    public function test_owner_can_reset_password(): void
    {
        $owner = User::create(['name' => 'Boss Owner', 'email' => 'boss@example.test', 'password' => 'password', 'role' => 'owner', 'is_active' => true]);
        $target = User::create(['name' => 'Target', 'email' => 'target@example.test', 'password' => Hash::make('oldpassword'), 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($owner)->post(route('company.users.reset-password', $target), [
            'password' => 'brandnewpassword123',
            'password_confirmation' => 'brandnewpassword123',
        ])->assertRedirect(route('company.users.index'));

        $target->refresh();
        $this->assertTrue(Hash::check('brandnewpassword123', $target->password));

        // Password is NOT leaked in log
        $log = CompanyActivityLog::where('action', 'password_reset')->latest('id')->firstOrFail();
        $this->assertStringNotContainsString('brandnewpassword123', json_encode($log->properties));
    }

    public function test_owner_can_view_and_filter_activity_logs(): void
    {
        $owner = User::create(['name' => 'Boss Owner', 'email' => 'boss@example.test', 'password' => 'password', 'role' => 'owner', 'is_active' => true]);

        CompanyActivityLog::create([
            'user_id' => $owner->id,
            'user_name' => $owner->name,
            'action' => 'user_created',
            'description' => 'Test log description for user created',
            'created_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('company.activity-logs.index'))
            ->assertOk()
            ->assertSee('Log Aktivitas Perusahaan')
            ->assertSee('Test log description for user created');

        // Filter by action
        $this->actingAs($owner)
            ->get(route('company.activity-logs.index', ['action' => 'user_created']))
            ->assertOk()
            ->assertSee('user_created');
    }

    public function test_admin_is_forbidden_from_activity_logs(): void
    {
        $admin = User::create(['name' => 'Staff Admin', 'email' => 'staff@example.test', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get(route('company.activity-logs.index'))->assertForbidden();
    }
}
