<?php
$file = 'tests/Feature/WorkspaceSelectionAndAdminExitTest.php';
$content = file_get_contents($file);

$oldCode = <<<'CODE'
    public function test_employee_must_reauthenticate_with_account_password_to_leave_workspace(): void
    {
        $leader = User::create(['name' => 'Employee Owner', 'email' => 'employee-owner@example.test', 'password' => 'password']);
        $employee = User::create([
            'name' => 'Employee',
            'email' => 'workspace-password@example.test',
            'password' => Hash::make('account-secret'),
        ]);
        $group = $this->createGroup($leader, 'Employee password workspace');
        $group->update(['password' => Hash::make('workspace-secret')]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $employee->id, 'role' => 'admin', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);

        $this->actingAs($leader)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertSee('Masukkan Password Workspace');

        $this->actingAs($leader)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Password workspace salah.');

        $this->actingAs($leader)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'workspace-secret',
            ])
            ->assertOk()
            ->assertJsonPath('redirect', route('group.workspace', [$group->id, $sheet->id]));

        // Direct navigation to dashboard is guarded
        $this->actingAs($leader)
            ->get(route('dashboard'))
            ->assertRedirect(route('group.workspace', [$group->id, $sheet->id]))
            ->assertSessionHas('workspace_exit_target', '/dashboard');

        // Wrong password leaves user in workspace
        $this->actingAs($leader)
            ->post(route('groups.leave', $group), [
                'password' => 'wrong-account-password',
                'destination' => '/dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue(session()->has('admin_workspace_context'));

        // Correct account password allows exit to destination
        $this->actingAs($leader)
            ->post(route('groups.leave', $group), [
                'password' => 'account-secret',
                'destination' => '/dashboard',
            ])
            ->assertRedirect('/dashboard');
        $this->assertFalse(session()->has('admin_workspace_context'));

        // Re-entering and leaving again requires password again
        $this->actingAs($leader)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'workspace-secret',
            ])
            ->assertOk();

        $this->actingAs($leader)
            ->get(route('dashboard'))
            ->assertRedirect(route('group.workspace', [$group->id, $sheet->id]))
            ->assertSessionHas('workspace_exit_target', '/dashboard');
    }

    public function test_user_navigating_directly_to_another_workspace_must_reauthenticate_previous_workspace_first(): void
    {
        $leader = User::create(['name' => 'Multi Owner', 'email' => 'multi-owner@example.test', 'password' => 'password']);
        $employee = User::create([
            'name' => 'Multi Employee',
            'email' => 'multi-employee@example.test',
            'password' => Hash::make('account-secret'),
        ]);

        $firstGroup = $this->createGroup($leader, 'First Workspace');
        $secondGroup = $this->createGroup($leader, 'Second Workspace');

        GroupMember::create(['group_id' => $firstGroup->id, 'user_id' => $employee->id, 'role' => 'admin', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $secondGroup->id, 'user_id' => $employee->id, 'role' => 'admin', 'status' => 'Aktif']);

        $firstSheet = $firstGroup->sheets()->create(['name' => 'Sheet 1', 'category' => 'analisa']);
        $secondSheet = $secondGroup->sheets()->create(['name' => 'Sheet 2', 'category' => 'analisa']);

        // Enter first workspace
        $this->actingAs($leader)
            ->get(route('group.workspace', [$firstGroup->id, $firstSheet->id]))
            ->assertOk();

        // Attempting to visit second workspace directly is guarded by first workspace
        $targetPath = "/groups/{$secondGroup->id}/workspace/{$secondSheet->id}";
        $this->actingAs($leader)
            ->get($targetPath)
            ->assertRedirect(route('group.workspace', [$firstGroup->id, $firstSheet->id]))
            ->assertSessionHas('workspace_exit_target', $targetPath);

        // Verify with account password to leave first workspace
        $this->actingAs($leader)
            ->post(route('groups.leave', $firstGroup), [
                'password' => 'account-secret',
                'destination' => $targetPath,
            ])
            ->assertRedirect($targetPath);

        $this->assertFalse(session()->has('admin_workspace_context'));
    }
CODE;

$newCode = <<<'NEWCODE'
    public function test_admin_must_reauthenticate_with_account_password_to_leave_workspace(): void
    {
        $leader = User::create(['name' => 'Admin Owner', 'email' => 'admin-owner@example.test', 'password' => 'password']);
        $admin = User::create([
            'name' => 'Admin Member',
            'email' => 'workspace-password@example.test',
            'password' => Hash::make('account-secret'),
        ]);
        $group = $this->createGroup($leader, 'Admin password workspace');
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);

        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertSee('Verifikasi Password Akun');

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Password akun salah. Silakan coba lagi.');

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'account-secret',
            ])
            ->assertOk()
            ->assertJsonPath('redirect', route('group.workspace', [$group->id, $sheet->id]));

        // Direct navigation to dashboard is guarded
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('group.workspace', [$group->id, $sheet->id]))
            ->assertSessionHas('workspace_exit_target', '/dashboard');

        // Wrong password leaves user in workspace
        $this->actingAs($admin)
            ->post(route('groups.leave', $group), [
                'password' => 'wrong-account-password',
                'destination' => '/dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue(session()->has('admin_workspace_context'));

        // Correct account password allows exit to destination
        $this->actingAs($admin)
            ->post(route('groups.leave', $group), [
                'password' => 'account-secret',
                'destination' => '/dashboard',
            ])
            ->assertRedirect('/dashboard');
        $this->assertFalse(session()->has('admin_workspace_context'));
    }

    public function test_user_navigating_directly_to_another_workspace_must_reauthenticate_previous_workspace_first(): void
    {
        $leader = User::create(['name' => 'Multi Owner', 'email' => 'multi-owner@example.test', 'password' => 'password']);
        $admin = User::create([
            'name' => 'Multi Admin',
            'email' => 'multi-admin@example.test',
            'password' => Hash::make('account-secret'),
        ]);

        $firstGroup = $this->createGroup($leader, 'First Workspace');
        $secondGroup = $this->createGroup($leader, 'Second Workspace');

        GroupMember::create(['group_id' => $firstGroup->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $secondGroup->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);

        $firstSheet = $firstGroup->sheets()->create(['name' => 'Sheet 1', 'category' => 'analisa']);
        $secondSheet = $secondGroup->sheets()->create(['name' => 'Sheet 2', 'category' => 'analisa']);

        // Unlock first workspace as admin
        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $firstGroup), [
                'sheet_id' => $firstSheet->id,
                'password' => 'account-secret',
            ])
            ->assertOk();

        // Enter first workspace to register active session context
        $this->actingAs($admin)
            ->get(route('group.workspace', [$firstGroup->id, $firstSheet->id]))
            ->assertOk();

        // Attempting to visit second workspace directly is guarded by first workspace
        $targetPath = "/groups/{$secondGroup->id}/workspace/{$secondSheet->id}";
        $this->actingAs($admin)
            ->get($targetPath)
            ->assertRedirect(route('group.workspace', [$firstGroup->id, $firstSheet->id]))
            ->assertSessionHas('workspace_exit_target', $targetPath);

        // Verify with account password to leave first workspace
        $this->actingAs($admin)
            ->post(route('groups.leave', $firstGroup), [
                'password' => 'account-secret',
                'destination' => $targetPath,
            ])
            ->assertRedirect($targetPath);

        $this->assertFalse(session()->has('admin_workspace_context'));
    }
NEWCODE;

// Normalize line endings before replace
$contentNorm = str_replace("\r\n", "\n", $content);
$oldNorm = str_replace("\r\n", "\n", $oldCode);
$newNorm = str_replace("\r\n", "\n", $newCode);

if (str_contains($contentNorm, $oldNorm)) {
    $contentNorm = str_replace($oldNorm, $newNorm, $contentNorm);
    file_put_contents($file, str_replace("\n", "\r\n", $contentNorm));
    echo "Replaced successfully\n";
} else {
    echo "Pattern not found!\n";
}

