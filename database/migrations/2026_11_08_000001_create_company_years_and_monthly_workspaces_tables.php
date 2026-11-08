<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Operational Years Table (Normalized: 1 row per year, unique year number)
        Schema::create('operational_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_archived')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Add legacy tracking & operational metadata to fisheries_groups
        Schema::table('fisheries_groups', function (Blueprint $table) {
            $table->boolean('is_legacy')->default(false)->after('created_by');
            $table->unsignedSmallInteger('operational_year')->nullable()->after('is_legacy');
            $table->unsignedTinyInteger('operational_month')->nullable()->after('operational_year');
        });

        // 3. Monthly Workspaces Table (12 months per year, unique per year+month)
        Schema::create('monthly_workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->constrained('operational_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('month'); // 1 to 12
            $table->string('name', 50); // Januari .. Desember
            $table->foreignId('group_id')->unique()->constrained('fisheries_groups')->cascadeOnDelete();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['year_id', 'month'], 'unique_year_month');
        });

        // 4. Add monthly_workspace_id and unique sheet name per workspace to spreadsheet_sheets
        Schema::table('spreadsheet_sheets', function (Blueprint $table) {
            $table->foreignId('monthly_workspace_id')->nullable()->after('group_id')->constrained('monthly_workspaces')->cascadeOnDelete();
            $table->unique(['group_id', 'name'], 'unique_group_sheet_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spreadsheet_sheets', function (Blueprint $table) {
            $table->dropUnique('unique_group_sheet_name');
            $table->dropForeign(['monthly_workspace_id']);
            $table->dropColumn('monthly_workspace_id');
        });

        Schema::dropIfExists('monthly_workspaces');

        Schema::table('fisheries_groups', function (Blueprint $table) {
            $table->dropColumn(['is_legacy', 'operational_year', 'operational_month']);
        });

        Schema::dropIfExists('operational_years');
    }
};

