<?php

namespace Database\Seeders;

use App\Services\CompanyWorkspaceInitializer;
use Illuminate\Database\Seeder;

class CompanyWorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $initializer = app(CompanyWorkspaceInitializer::class);
        $result = $initializer->initialize();

        $this->command?->info("Company Workspace Initialization Complete:");
        $this->command?->info("- Legacy Groups Marked: {$result['legacy_marked']}");
        $this->command?->info("- Operational Years Created: {$result['years_created']} (Total: {$result['total_years']})");
        $this->command?->info("- Monthly Workspaces Created: {$result['workspaces_created']} (Total: {$result['total_monthly_workspaces']})");
        $this->command?->info("- Spreadsheets Created: {$result['sheets_created']} (Total with workspace: {$result['total_sheets_with_workspace']})");
    }
}

