<?php

namespace Tests\Feature;

use App\Models\FisheriesGroup;
use App\Models\OperationalYear;
use App\Models\User;
use App\Services\CompanyWorkspaceInitializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemovedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_workspace_and_join_team_are_absent_from_dashboard(): void
    {
        $owner = User::create(['name' => 'Owner User', 'email' => 'owner@example.test', 'password' => 'password', 'role' => 'owner']);

        $response = $this->actingAs($owner)->get(route('dashboard'));
        $response->assertOk();

        // Dashboard quick action buttons removed
        $response->assertDontSee('modal-create-group');
        $response->assertDontSee('modal-join-group');
        $response->assertDontSee('+ Buat Workspace Baru');
    }

    public function test_removed_routes_cannot_be_accessed_directly(): void
    {
        $user = User::create(['name' => 'Some User', 'email' => 'some@example.test', 'password' => 'password', 'role' => 'admin']);

        // POST /groups (createGroup) is removed and access is denied
        $res1 = $this->actingAs($user)->post('/groups', ['name' => 'Forbidden Group', 'password' => 'secret']);
        $this->assertTrue(in_array($res1->status(), [404, 405], true));

        // POST /groups/join (joinGroup) is removed and access is denied
        $res2 = $this->actingAs($user)->post('/groups/join', ['workspace_name' => 'Some WS', 'password' => 'secret']);
        $this->assertTrue(in_array($res2->status(), [404, 405], true));
    }

    public function test_internal_workspace_provisioning_remains_fully_functional(): void
    {
        $owner = User::create(['name' => 'Owner User', 'email' => 'owner2@example.test', 'password' => 'password', 'role' => 'owner']);

        $initializer = app(CompanyWorkspaceInitializer::class);
        $opYear = $initializer->provisionYear(2028, $owner->id, false);

        $this->assertInstanceOf(OperationalYear::class, $opYear);
        $this->assertSame(2028, $opYear->year);
        $this->assertSame(12, $opYear->monthlyWorkspaces()->count());

        foreach ($opYear->monthlyWorkspaces as $mw) {
            $this->assertNotNull($mw->group);
            $this->assertSame(5, $mw->group->sheets()->count());
        }
    }
}
