<?php

namespace Tests\Feature;

use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\SpreadsheetActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiLeaderMultiAdminWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1 & 2: Workspace can have two, three, or more Leaders without restriction.
     */
    public function test_workspace_supports_multiple_leaders_without_limit(): void
    {
        $founder = User::create(['name' => 'Founder Chief', 'email' => 'founder@test.com', 'password' => Hash::make('secret123')]);
        $leader2 = User::create(['name' => 'Leader Budi', 'email' => 'budi@test.com', 'password' => Hash::make('secret123')]);
        $leader3 = User::create(['name' => 'Leader Charlie', 'email' => 'charlie@test.com', 'password' => Hash::make('secret123')]);
        $leader4 = User::create(['name' => 'Leader Diana', 'email' => 'diana@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Multi-Leader Farm Workspace',
            'invite_code' => 'WS-ML-01',
            'created_by' => $founder->id,
        ]);

        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader2->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader3->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader4->id, 'role' => 'leader', 'status' => 'Aktif']);

        $this->assertSame(4, $group->countActiveLeaders());
        $this->assertTrue($group->isLeader($founder->id));
        $this->assertTrue($group->isLeader($leader2->id));
        $this->assertTrue($group->isLeader($leader3->id));
        $this->assertTrue($group->isLeader($leader4->id));
    }

    /**
     * Test 3: Workspace can have many Admins without restriction.
     */
    public function test_workspace_supports_multiple_admins_without_limit(): void
    {
        $founder = User::create(['name' => 'Founder Chief', 'email' => 'founder_admin@test.com', 'password' => Hash::make('secret123')]);
        $group = FisheriesGroup::create([
            'name' => 'Multi-Admin Farm Workspace',
            'invite_code' => 'WS-MA-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);

        for ($i = 1; $i <= 5; $i++) {
            $adminUser = User::create(['name' => "Admin {$i}", 'email' => "admin{$i}@test.com", 'password' => Hash::make('secret123')]);
            GroupMember::create(['group_id' => $group->id, 'user_id' => $adminUser->id, 'role' => 'admin', 'status' => 'Aktif']);
            $this->assertTrue($group->isAdmin($adminUser->id));
        }

        $adminCount = $group->members()->where('role', 'admin')->where('status', 'Aktif')->count();
        $this->assertSame(5, $adminCount);
    }

    /**
     * Test 4 & 7: Leader can promote an Admin to Leader without demoting existing leaders.
     */
    public function test_leader_can_promote_admin_to_leader_independently(): void
    {
        $founder = User::create(['name' => 'Founder Leader', 'email' => 'fl@test.com', 'password' => Hash::make('secret123')]);
        $admin = User::create(['name' => 'Admin To Promote', 'email' => 'ap@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Promotion Workspace',
            'invite_code' => 'WS-PR-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $adminMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);

        $response = $this->actingAs($founder)->putJson(
            route('groups.members.update', [$group->id, $adminMember->id]),
            ['role' => 'leader']
        );

        $response->assertOk();
        $response->assertJson(['new_role' => 'leader']);

        $adminMember->refresh();
        $this->assertSame('leader', $adminMember->role);

        // Founder must STILL be a leader (no automatic leadership transfer!)
        $this->assertTrue($group->isLeader($founder->id));
        $this->assertSame(2, $group->countActiveLeaders());
    }

    /**
     * Test 5: Leader can add a new member directly as Leader or as Admin.
     */
    public function test_leader_can_add_new_member_as_leader_or_admin(): void
    {
        $founder = User::create(['name' => 'Founder Chief', 'email' => 'fc_add@test.com', 'password' => Hash::make('secret123')]);
        $newMember1 = User::create(['name' => 'New Guy 1', 'email' => 'new1@test.com', 'password' => Hash::make('secret123')]);
        $newMember2 = User::create(['name' => 'New Guy 2', 'email' => 'new2@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Add Member Workspace',
            'invite_code' => 'WS-ADD-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);

        // 1. Add as Leader
        $resLeader = $this->actingAs($founder)->post(route('groups.members.add', $group->id), [
            'user_id' => $newMember1->id,
            'role' => 'leader',
        ]);
        $resLeader->assertRedirect();
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $newMember1->id,
            'role' => 'leader',
            'status' => 'Aktif',
        ]);

        // 2. Add as Admin
        $resAdmin = $this->actingAs($founder)->post(route('groups.members.add', $group->id), [
            'user_id' => $newMember2->id,
            'role' => 'admin',
        ]);
        $resAdmin->assertRedirect();
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $newMember2->id,
            'role' => 'admin',
            'status' => 'Aktif',
        ]);
    }

    /**
     * Test 6: A promoted leader can be demoted back to Admin by another Leader.
     */
    public function test_promoted_leader_can_be_demoted_to_admin_by_another_leader(): void
    {
        $founder = User::create(['name' => 'Founder Leader', 'email' => 'founder_dem@test.com', 'password' => Hash::make('secret123')]);
        $promotedLeader = User::create(['name' => 'Promoted Leader', 'email' => 'promoted@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Demotion Workspace',
            'invite_code' => 'WS-DEM-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $promotedMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $promotedLeader->id, 'role' => 'leader', 'status' => 'Aktif']);

        $this->assertSame(2, $group->countActiveLeaders());

        // Demote back to admin
        $res = $this->actingAs($founder)->putJson(
            route('groups.members.update', [$group->id, $promotedMember->id]),
            ['role' => 'admin']
        );
        $res->assertOk();
        $res->assertJson(['new_role' => 'admin']);

        $promotedMember->refresh();
        $this->assertSame('admin', $promotedMember->role);
        $this->assertSame(1, $group->countActiveLeaders());
    }

    /**
     * Test 8: Founder role is permanently protected in backend and cannot be changed.
     */
    public function test_founder_role_is_permanently_protected_against_modification(): void
    {
        $founder = User::create(['name' => 'Founder Chief', 'email' => 'founder_prot@test.com', 'password' => Hash::make('secret123')]);
        $coLeader = User::create(['name' => 'Co-Leader', 'email' => 'coleader@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Protected Workspace',
            'invite_code' => 'WS-PROT-01',
            'created_by' => $founder->id,
        ]);
        $founderMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $coLeader->id, 'role' => 'leader', 'status' => 'Aktif']);

        // Another leader attempts to demote the Founder
        $response = $this->actingAs($coLeader)->putJson(
            route('groups.members.update', [$group->id, $founderMember->id]),
            ['role' => 'admin']
        );
        $response->assertForbidden();

        $founderMember->refresh();
        $this->assertSame('leader', $founderMember->role);
    }

    /**
     * Test 9: Founder cannot be removed through member removal endpoint.
     */
    public function test_founder_cannot_be_removed_from_workspace(): void
    {
        $founder = User::create(['name' => 'Founder Unremovable', 'email' => 'unrem@test.com', 'password' => Hash::make('secret123')]);
        $coLeader = User::create(['name' => 'Second Leader', 'email' => 'sec@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Unremovable Workspace',
            'invite_code' => 'WS-UNREM-01',
            'created_by' => $founder->id,
        ]);
        $founderMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $coLeader->id, 'role' => 'leader', 'status' => 'Aktif']);

        $response = $this->actingAs($coLeader)->deleteJson(
            route('groups.members.remove', [$group->id, $founderMember->id])
        );
        $response->assertForbidden();

        $this->assertDatabaseHas('group_members', ['id' => $founderMember->id]);
    }

    /**
     * Test 10: Admin cannot change any member's role.
     */
    public function test_admin_cannot_change_any_members_role(): void
    {
        $founder = User::create(['name' => 'Founder Leader', 'email' => 'fl_adm@test.com', 'password' => Hash::make('secret123')]);
        $admin = User::create(['name' => 'Admin User', 'email' => 'adm_usr@test.com', 'password' => Hash::make('secret123')]);
        $target = User::create(['name' => 'Target User', 'email' => 'tgt_usr@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Admin Restrict Workspace',
            'invite_code' => 'WS-REST-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        $targetMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $target->id, 'role' => 'admin', 'status' => 'Aktif']);

        // Admin tries to change target user to Leader
        $response = $this->actingAs($admin)->putJson(
            route('groups.members.update', [$group->id, $targetMember->id]),
            ['role' => 'leader']
        );
        $response->assertForbidden();

        $targetMember->refresh();
        $this->assertSame('admin', $targetMember->role);
    }

    /**
     * Test 11: Member cannot promote or modify their own role.
     */
    public function test_member_cannot_modify_own_role(): void
    {
        $founder = User::create(['name' => 'Founder Leader', 'email' => 'fl_self@test.com', 'password' => Hash::make('secret123')]);
        $admin = User::create(['name' => 'Selfish Admin', 'email' => 'self@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Self Promotion Restrict Workspace',
            'invite_code' => 'WS-SELF-01',
            'created_by' => $founder->id,
        ]);
        $founderMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $adminMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);

        // Admin attempts to promote self to leader
        $resAdmin = $this->actingAs($admin)->putJson(
            route('groups.members.update', [$group->id, $adminMember->id]),
            ['role' => 'leader']
        );
        $resAdmin->assertForbidden();

        // Founder attempts to change self
        $resFounder = $this->actingAs($founder)->putJson(
            route('groups.members.update', [$group->id, $founderMember->id]),
            ['role' => 'admin']
        );
        $resFounder->assertForbidden();
    }

    /**
     * Test 12: Role Karyawan is not available in frontend.
     */
    public function test_karyawan_role_not_available_in_frontend(): void
    {
        $founder = User::create(['name' => 'Founder Leader', 'email' => 'fe_fl@test.com', 'password' => Hash::make('secret123')]);
        $group = FisheriesGroup::create([
            'name' => 'Frontend Role Workspace',
            'invite_code' => 'WS-FE-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $sheet->initializeBlankGrid();

        $response = $this->actingAs($founder)->get(route('group.workspace', [$group->id, $sheet->id]));
        $response->assertOk();

        // Ensure "karyawan" or "Karyawan" is not in role select or options
        $response->assertDontSee('value="karyawan"', false);
        $response->assertDontSee('Karyawan');
    }

    /**
     * Test 13: Role Karyawan is rejected by backend.
     */
    public function test_karyawan_role_is_rejected_by_backend_validation(): void
    {
        $founder = User::create(['name' => 'Founder Leader', 'email' => 'rej_fl@test.com', 'password' => Hash::make('secret123')]);
        $memberUser = User::create(['name' => 'Candidate', 'email' => 'cand@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Reject Karyawan Workspace',
            'invite_code' => 'WS-REJ-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $targetMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $memberUser->id, 'role' => 'admin', 'status' => 'Aktif']);

        // Updating role to karyawan must fail validation (422)
        $responseUpdate = $this->actingAs($founder)->putJson(
            route('groups.members.update', [$group->id, $targetMember->id]),
            ['role' => 'karyawan']
        );
        $responseUpdate->assertStatus(422);

        // Adding member with role karyawan must fail validation
        $responseAdd = $this->actingAs($founder)->post(
            route('groups.members.add', $group->id),
            ['user_id' => $memberUser->id, 'role' => 'karyawan']
        );
        $responseAdd->assertSessionHasErrors('role');
    }

    /**
     * Test 14: Migration accurately converts legacy/karyawan roles to admin.
     */
    public function test_legacy_karyawan_roles_migrated_to_admin(): void
    {
        $founder = User::create(['name' => 'Founder Old', 'email' => 'mig_fl@test.com', 'password' => Hash::make('secret123')]);
        $legacyUser = User::create(['name' => 'Legacy Karyawan', 'email' => 'mig_old@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Migration Workspace',
            'invite_code' => 'WS-MIG-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $legacyMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $legacyUser->id, 'role' => 'karyawan', 'status' => 'Aktif']);

        // Run normalization logic (same as migration up)
        \Illuminate\Support\Facades\DB::table('group_members')
            ->whereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(role))'), ['leader', 'admin'])
            ->update(['role' => 'admin']);

        $legacyMember->refresh();
        $this->assertSame('admin', $legacyMember->role);
        $this->assertSame('admin', $group->getMemberRole($legacyUser->id));
    }

    /**
     * Test 15 & 16: Changes persist across DB and user sessions.
     */
    public function test_role_changes_persist_in_database_and_across_sessions(): void
    {
        $founder = User::create(['name' => 'Founder Leader', 'email' => 'sess_fl@test.com', 'password' => Hash::make('secret123')]);
        $candidate = User::create(['name' => 'Promoted Admin', 'email' => 'sess_cand@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Session Persist Workspace',
            'invite_code' => 'WS-SESS-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $candidateMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $candidate->id, 'role' => 'admin', 'status' => 'Aktif']);

        // Promote to leader
        $this->actingAs($founder)->putJson(
            route('groups.members.update', [$group->id, $candidateMember->id]),
            ['role' => 'leader']
        )->assertOk();

        // Logout founder, login candidate
        auth()->logout();

        $this->actingAs($candidate);
        $this->assertTrue($group->fresh()->isLeader($candidate->id));
        $this->assertSame('leader', $candidateMember->fresh()->role);
    }

    /**
     * Test 17: Role changes in Workspace A do NOT affect permissions in Workspace B.
     */
    public function test_role_changes_do_not_affect_other_workspaces(): void
    {
        $founderA = User::create(['name' => 'Founder A', 'email' => 'fa@test.com', 'password' => Hash::make('secret123')]);
        $founderB = User::create(['name' => 'Founder B', 'email' => 'fb@test.com', 'password' => Hash::make('secret123')]);
        $sharedUser = User::create(['name' => 'Shared Member', 'email' => 'shared@test.com', 'password' => Hash::make('secret123')]);

        $groupA = FisheriesGroup::create(['name' => 'Workspace A', 'invite_code' => 'WS-A', 'created_by' => $founderA->id]);
        $groupB = FisheriesGroup::create(['name' => 'Workspace B', 'invite_code' => 'WS-B', 'created_by' => $founderB->id]);

        GroupMember::create(['group_id' => $groupA->id, 'user_id' => $founderA->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $groupB->id, 'user_id' => $founderB->id, 'role' => 'leader', 'status' => 'Aktif']);

        $memberA = GroupMember::create(['group_id' => $groupA->id, 'user_id' => $sharedUser->id, 'role' => 'admin', 'status' => 'Aktif']);
        $memberB = GroupMember::create(['group_id' => $groupB->id, 'user_id' => $sharedUser->id, 'role' => 'admin', 'status' => 'Aktif']);

        // Promote sharedUser in Workspace A to Leader
        $this->actingAs($founderA)->putJson(
            route('groups.members.update', [$groupA->id, $memberA->id]),
            ['role' => 'leader']
        )->assertOk();

        // SharedUser should be Leader in A, but still Admin in B
        $this->assertTrue($groupA->isLeader($sharedUser->id));
        $this->assertSame('leader', $memberA->fresh()->role);

        $this->assertFalse($groupB->isLeader($sharedUser->id));
        $this->assertTrue($groupB->isAdmin($sharedUser->id));
        $this->assertSame('admin', $memberB->fresh()->role);
    }

    /**
     * Test 18: Activity log properly records role changes with actor, target, old role, new role, and workspace.
     */
    public function test_activity_log_records_role_changes_with_full_details(): void
    {
        $founder = User::create(['name' => 'Founder Actor', 'email' => 'actor@test.com', 'password' => Hash::make('secret123')]);
        $target = User::create(['name' => 'Target Member', 'email' => 'target@test.com', 'password' => Hash::make('secret123')]);

        $group = FisheriesGroup::create([
            'name' => 'Activity Workspace',
            'invite_code' => 'WS-ACT-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $targetMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $target->id, 'role' => 'admin', 'status' => 'Aktif']);

        $this->actingAs($founder)->putJson(
            route('groups.members.update', [$group->id, $targetMember->id]),
            ['role' => 'leader']
        )->assertOk();

        $this->assertDatabaseHas('spreadsheet_activities', [
            'group_id' => $group->id,
            'user_id' => $founder->id,
            'action' => 'role_changed',
            'old_value' => 'Admin',
            'new_value' => 'Leader',
        ]);

        $log = SpreadsheetActivity::where('group_id', $group->id)
            ->where('action', 'role_changed')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Founder Actor', $log->details['actor_name']);
        $this->assertSame('Target Member', $log->details['target_user_name']);
        $this->assertSame('admin', $log->details['old_role']);
        $this->assertSame('leader', $log->details['new_role']);
        $this->assertSame($group->name, $log->details['workspace_name']);
    }
}
