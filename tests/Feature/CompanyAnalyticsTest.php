<?php

namespace Tests\Feature;

use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\MonthlyWorkspace;
use App\Models\OperationalYear;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetSheet;
use App\Models\User;
use App\Services\CompanyAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create verified Owner
        $this->owner = User::create([
            'name' => 'Dadhen Owner',
            'email' => 'owner_analytics@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'role_title' => 'Owner',
        ]);

        // 2. Create verified Admin
        $this->admin = User::create([
            'name' => 'Regular Admin',
            'email' => 'admin_analytics@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'role_title' => 'Admin',
        ]);
    }

    public function test_owner_can_access_all_company_analytics_tabs(): void
    {
        $year = OperationalYear::create([
            'year' => 2025,
            'is_active' => true,
            'is_archived' => false,
            'created_by' => $this->owner->id,
        ]);

        // Tab 1: Overview
        $resOverview = $this->actingAs($this->owner)->get(route('company.analytics', ['tab' => 'overview']));
        $resOverview->assertOk();
        $resOverview->assertSee('Company Analytics');
        $resOverview->assertSee('1. Overview');

        // Tab 2: Financial Analytics
        $resFinancial = $this->actingAs($this->owner)->get(route('company.analytics', ['tab' => 'financial']));
        $resFinancial->assertOk();
        $resFinancial->assertSee('2. Financial Analytics');

        // Tab 3: Operational Analytics
        $resOperational = $this->actingAs($this->owner)->get(route('company.analytics', ['tab' => 'operational']));
        $resOperational->assertOk();
        $resOperational->assertSee('3. Operational Analytics');

        // Tab 4: Yearly Comparison
        $resComparisonTab = $this->actingAs($this->owner)->get(route('company.analytics', ['tab' => 'comparison']));
        $resComparisonTab->assertOk();
        $resComparisonTab->assertSee('4. Yearly Comparison');

        // Direct Route: Yearly Comparison
        $resComparisonDirect = $this->actingAs($this->owner)->get(route('company.yearly-comparison'));
        $resComparisonDirect->assertOk();
        $resComparisonDirect->assertSee('Yearly Comparison');
    }

    public function test_admin_is_strictly_forbidden_from_company_analytics(): void
    {
        $year = OperationalYear::create([
            'year' => 2025,
            'is_active' => true,
            'is_archived' => false,
            'created_by' => $this->owner->id,
        ]);

        // Admin attempting to access Overview
        $this->actingAs($this->admin)
            ->get(route('company.analytics'))
            ->assertForbidden();

        // Admin attempting to access Financial tab
        $this->actingAs($this->admin)
            ->get(route('company.analytics', ['tab' => 'financial']))
            ->assertForbidden();

        // Admin attempting to access Operational tab
        $this->actingAs($this->admin)
            ->get(route('company.analytics', ['tab' => 'operational']))
            ->assertForbidden();

        // Admin attempting to access Comparison
        $this->actingAs($this->admin)
            ->get(route('company.yearly-comparison'))
            ->assertForbidden();

        // Admin attempting to fetch JSON analytics endpoint
        $this->actingAs($this->admin)
            ->getJson(route('company.analytics'))
            ->assertForbidden();
    }

    public function test_financial_analytics_computes_verified_metrics_against_sample_fixture(): void
    {
        $year = OperationalYear::create([
            'year' => 2025,
            'is_active' => true,
            'is_archived' => false,
            'created_by' => $this->owner->id,
        ]);

        $group = FisheriesGroup::create([
            'name' => 'Januari 2025',
            'category' => 'Bulanan',
            'invite_code' => 'OPS202501',
            'created_by' => $this->owner->id,
            'operational_year' => 2025,
            'operational_month' => 1,
        ]);

        $mw = MonthlyWorkspace::create([
            'year_id' => $year->id,
            'month' => 1,
            'name' => 'Januari',
            'group_id' => $group->id,
            'status' => 'active',
        ]);

        $rankSheet = $group->sheets()->create([
            'name' => 'Rank',
            'category' => 'rank',
            'monthly_workspace_id' => $mw->id,
        ]);

        // Create column C
        $colC = $rankSheet->columns()->create([
            'letter' => 'C',
            'title' => 'Total',
            'sort_order' => 3,
            'width' => 160,
        ]);

        // Create rows for Rank
        $rowsData = [
            2 => 500000000, // Omset: 500 Juta
            3 => 300000000, // RM: 300 Juta
            4 => 200000000, // Margin: 200 Juta
            5 => 50000000,  // Cost: 50 Juta
            6 => 60000000,  // Payroll: 60 Juta
            7 => 90000000,  // Nett: 90 Juta
            13 => 20000000, // Bank: 20 Juta
        ];

        foreach ($rowsData as $order => $val) {
            $row = $rankSheet->rows()->create(['sort_order' => $order]);
            SpreadsheetCell::create([
                'row_id' => $row->id,
                'column_id' => $colC->id,
                'value' => (string) $val,
                'formula' => null,
                'version' => 1,
            ]);
        }

        // Test calculation via CompanyAnalyticsService
        $service = app(CompanyAnalyticsService::class);
        $metrics = $service->calculateYearMetrics($year);

        $fin = $metrics['financial_summary'];

        // Verified manually calculated assertions
        $this->assertEquals(500000000.0, $fin['total_omset']);
        $this->assertEquals(300000000.0, $fin['total_rm']);
        $this->assertEquals(200000000.0, $fin['total_margin']);
        $this->assertEquals(40.0, $fin['margin_pct']); // (200m / 500m) * 100
        $this->assertEquals(50000000.0, $fin['total_cost']);
        $this->assertEquals(60000000.0, $fin['total_payroll']);
        $this->assertEquals(90000000.0, $fin['total_nett']);
        $this->assertEquals(18.0, $fin['net_profit_margin_pct']); // (90m / 500m) * 100
        $this->assertEquals(20000000.0, $fin['total_bank']);

        // Check response on page
        $res = $this->actingAs($this->owner)->get(route('company.analytics', ['tab' => 'financial', 'year' => 2025]));
        $res->assertOk();
        $res->assertSee('500.000.000');
        $res->assertSee('200.000.000');
        $res->assertSee('90.000.000');
    }

    public function test_operational_analytics_computes_verified_metrics_against_sample_fixture(): void
    {
        $year = OperationalYear::create([
            'year' => 2025,
            'is_active' => true,
            'is_archived' => false,
            'created_by' => $this->owner->id,
        ]);

        $group = FisheriesGroup::create([
            'name' => 'Januari 2025',
            'category' => 'Bulanan',
            'invite_code' => 'OPS202501',
            'created_by' => $this->owner->id,
            'operational_year' => 2025,
            'operational_month' => 1,
        ]);

        $mw = MonthlyWorkspace::create([
            'year_id' => $year->id,
            'month' => 1,
            'name' => 'Januari',
            'group_id' => $group->id,
            'status' => 'active',
        ]);

        // Sheet: Analisa
        $analisaSheet = $group->sheets()->create([
            'name' => 'Analisa',
            'category' => 'analisa',
            'monthly_workspace_id' => $mw->id,
        ]);
        $colA = $analisaSheet->columns()->create(['letter' => 'A', 'title' => 'Input', 'sort_order' => 1, 'width' => 100]);

        // Row 4: RM Input (10,000 kg), Row 5: Fillet (4,000 kg), Row 17: Finished Goods (6,000 kg)
        $analisaRows = [
            4 => 10000,
            5 => 4000,
            17 => 6000,
        ];
        foreach ($analisaRows as $order => $val) {
            $row = $analisaSheet->rows()->create(['sort_order' => $order]);
            SpreadsheetCell::create([
                'row_id' => $row->id,
                'column_id' => $colA->id,
                'value' => (string) $val,
                'formula' => null,
                'version' => 1,
            ]);
        }

        // Sheet: Cost
        $costSheet = $group->sheets()->create([
            'name' => 'Cost',
            'category' => 'cost',
            'monthly_workspace_id' => $mw->id,
        ]);
        $colCost = $costSheet->columns()->create(['letter' => 'A', 'title' => 'Biaya', 'sort_order' => 1, 'width' => 100]);

        // Row 8: MC (2,500,000), Row 13: Listrik (4,000,000), Row 14: Air (1,200,000)
        $costRows = [
            8 => 2500000,
            13 => 4000000,
            14 => 1200000,
        ];
        foreach ($costRows as $order => $val) {
            $row = $costSheet->rows()->create(['sort_order' => $order]);
            SpreadsheetCell::create([
                'row_id' => $row->id,
                'column_id' => $colCost->id,
                'value' => (string) $val,
                'formula' => null,
                'version' => 1,
            ]);
        }

        $service = app(CompanyAnalyticsService::class);
        $metrics = $service->calculateYearMetrics($year);

        $ops = $metrics['operational_summary'];
        $utils = $metrics['utilities_and_supplies'];

        $this->assertEquals(10000.0, $ops['total_rm_kg']);
        $this->assertEquals(4000.0, $ops['total_fillet_kg']);
        $this->assertEquals(40.0, $ops['rendemen_fillet_pct']); // (4,000 / 10,000) * 100
        $this->assertEquals(6000.0, $ops['total_finished_goods_kg']);

        $this->assertEquals(2500000.0, $utils['packaging_mc']);
        $this->assertEquals(4000000.0, $utils['listrik']);
        $this->assertEquals(1200000.0, $utils['air']);
    }

    public function test_yearly_comparison_dynamically_includes_active_and_archived_years(): void
    {
        $year2024 = OperationalYear::create(['year' => 2024, 'is_active' => false, 'is_archived' => true]);
        $year2025 = OperationalYear::create(['year' => 2025, 'is_active' => true, 'is_archived' => false]);

        $service = app(CompanyAnalyticsService::class);
        $comparison = $service->getYearlyComparison();

        $this->assertCount(2, $comparison['comparison']);
        $this->assertEquals(2024, $comparison['comparison'][0]['year']);
        $this->assertTrue($comparison['comparison'][0]['is_archived']);
        $this->assertEquals(2025, $comparison['comparison'][1]['year']);
        $this->assertFalse($comparison['comparison'][1]['is_archived']);

        // Dynamically create 2026
        OperationalYear::create(['year' => 2026, 'is_active' => true, 'is_archived' => false]);
        $comparisonAfter = $service->getYearlyComparison();
        $this->assertCount(3, $comparisonAfter['comparison']);
        $this->assertEquals(2026, $comparisonAfter['comparison'][2]['year']);
    }

    public function test_empty_state_and_zero_fabrication_safety(): void
    {
        $year = OperationalYear::create(['year' => 2030, 'is_active' => true, 'is_archived' => false]);

        $service = app(CompanyAnalyticsService::class);
        $metrics = $service->calculateYearMetrics($year);

        // Verified zero fabrication: all numbers default to 0.0 with has_data = false
        $this->assertFalse($metrics['has_data']);
        $this->assertEquals(0.0, $metrics['financial_summary']['total_omset']);
        $this->assertEquals(0.0, $metrics['financial_summary']['total_nett']);
        $this->assertEquals(0.0, $metrics['operational_summary']['total_rm_kg']);
    }
}

