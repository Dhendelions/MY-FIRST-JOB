<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseDrivenRoleSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test A: Admin -> Owner Dynamic Role Synchronization via direct DB change.
     */
    public function test_admin_to_owner_dynamic_role_synchronization(): void
    {
        // 1. Create and log in as Admin
        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        // Confirm initial Admin state on Dashboard
        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Admin');
        $response->assertDontSee('Company Analytics');

        // Verify Owner routes are forbidden for Admin
        $this->get(route('company.analytics'))->assertForbidden();
        $this->get(route('company.users.index'))->assertForbidden();
        $this->get(route('company.activity-logs.index'))->assertForbidden();

        // 2. Direct MySQL update: UPDATE users SET role = 'owner' WHERE email = ...
        DB::table('users')->where('email', 'budi@example.test')->update(['role' => 'owner']);

        // 3. Next request / page refresh
        $refreshedResponse = $this->get(route('dashboard'));
        $refreshedResponse->assertOk();

        // Role below username changes to Owner
        $refreshedResponse->assertSee('Owner');
        $refreshedResponse->assertSee('Company Analytics');
        $refreshedResponse->assertSee('Manajemen User');

        // Owner permissions are immediately available
        $this->get(route('company.analytics'))->assertOk();
        $this->get(route('company.users.index'))->assertOk();
        $this->get(route('company.activity-logs.index'))->assertOk();

        // Model methods reflect database state
        $this->assertTrue($user->fresh()->isOwner());
        $this->assertFalse($user->fresh()->isAdmin());
        $this->assertSame('owner', $user->fresh()->role);
        $this->assertSame('Owner', $user->fresh()->display_role);
        $this->assertSame('Owner', $user->fresh()->role_title);
    }

    /**
     * Test B: Owner -> Admin Dynamic Role Synchronization via direct DB change.
     */
    public function test_owner_to_admin_dynamic_role_synchronization(): void
    {
        // Another active owner exists so last-active-owner check passes
        User::create([
            'name' => 'Root Owner',
            'email' => 'root@example.test',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $user = User::create([
            'name' => 'Dheni Pratama',
            'email' => 'dheni@example.test',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        // Confirm initial Owner state on Dashboard
        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Owner');
        $response->assertSee('Company Analytics');
        $this->get(route('company.analytics'))->assertOk();

        // 2. Direct MySQL update: UPDATE users SET role = 'admin' WHERE email = ...
        DB::table('users')->where('email', 'dheni@example.test')->update(['role' => 'admin']);

        // 3. Next request / page refresh
        $refreshedResponse = $this->get(route('dashboard'));
        $refreshedResponse->assertOk();

        // Role below username changes to Admin
        $refreshedResponse->assertSee('Admin');
        $refreshedResponse->assertDontSee('Company Analytics');
        $refreshedResponse->assertDontSee('Manajemen User');

        // Owner permissions are immediately revoked (HTTP 403)
        $this->get(route('company.analytics'))->assertForbidden();
        $this->get(route('company.users.index'))->assertForbidden();
        $this->get(route('company.activity-logs.index'))->assertForbidden();

        // Model methods reflect database state
        $this->assertFalse($user->fresh()->isOwner());
        $this->assertTrue($user->fresh()->isAdmin());
        $this->assertSame('admin', $user->fresh()->role);
        $this->assertSame('Admin', $user->fresh()->display_role);
        $this->assertSame('Admin', $user->fresh()->role_title);
    }

    /**
     * Test C: New Registration Default Role and Security Enforcement.
     */
    public function test_new_registration_automatically_receives_admin_role(): void
    {
        // 1. Submit normal registration form
        $response = $this->post(route('register'), [
            'name' => 'New Staff Member',
            'email' => 'newstaff@example.test',
            'whatsapp' => '081234567890',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // 2. Verify in database: role column is strictly 'admin'
        $dbUser = DB::table('users')->where('email', 'newstaff@example.test')->first();
        $this->assertNotNull($dbUser);
        $this->assertSame('admin', $dbUser->role);

        // 3. Frontend displays Admin below the username
        $dashResponse = $this->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Admin');
        $dashResponse->assertDontSee('Company Analytics');

        // 4. Verify user has Admin permissions (Owner endpoints are blocked)
        $this->get(route('company.analytics'))->assertForbidden();
        $this->get(route('company.users.index'))->assertForbidden();
    }

    /**
     * Test C.2: Injected role parameter during registration cannot create an Owner account.
     */
    public function test_injected_role_during_registration_is_rejected_or_ignored(): void
    {
        // Malicious user attempts to inject 'role' => 'owner' or 'role_title' => 'owner'
        $response = $this->post(route('register'), [
            'name' => 'Hacker Account',
            'email' => 'hacker@example.test',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'role' => 'owner',
            'role_title' => 'Owner',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Database record must still be 'admin'
        $dbUser = DB::table('users')->where('email', 'hacker@example.test')->first();
        $this->assertNotNull($dbUser);
        $this->assertSame('admin', $dbUser->role);

        $user = User::find($dbUser->id);
        $this->assertFalse($user->isOwner());
        $this->assertTrue($user->isAdmin());
        $this->assertSame('Admin', $user->display_role);

        // Cannot access Owner routes
        $this->get(route('company.analytics'))->assertForbidden();
    }

    /**
     * Test D: User model accessors and mutators guarantee 100% role synchronization.
     */
    public function test_user_model_role_accessors_and_serialization(): void
    {
        $admin = new User(['role' => 'admin']);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('Admin', $admin->role_title);
        $this->assertSame('Admin', $admin->display_role);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isOwner());

        $owner = new User(['role' => 'owner']);
        $this->assertSame('owner', $owner->role);
        $this->assertSame('Owner', $owner->role_title);
        $this->assertSame('Owner', $owner->display_role);
        $this->assertTrue($owner->isOwner());
        $this->assertFalse($owner->isAdmin());

        $array = $owner->toArray();
        $this->assertArrayHasKey('display_role', $array);
        $this->assertSame('Owner', $array['display_role']);
    }
}

