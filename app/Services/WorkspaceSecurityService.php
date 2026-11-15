<?php

namespace App\Services;

use App\Models\FisheriesGroup;
use App\Models\OperationalYear;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetSheet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WorkspaceSecurityService
{
    /**
     * Check if a workspace group is archived by directly querying the database.
     * Guaranteed to reflect immediate DB status changes without relying on in-memory relations.
     */
    public static function isGroupArchived(int|FisheriesGroup $group): bool
    {
        $groupId = $group instanceof FisheriesGroup ? $group->id : (int) $group;

        // 1. Check via monthly_workspaces and operational_years
        $isArchived = DB::table('monthly_workspaces')
            ->join('operational_years', 'monthly_workspaces.year_id', '=', 'operational_years.id')
            ->where('monthly_workspaces.group_id', $groupId)
            ->where('operational_years.is_archived', true)
            ->exists();

        if ($isArchived) {
            return true;
        }

        // 2. Check if the group has operational_year column
        $opYear = DB::table('fisheries_groups')->where('id', $groupId)->value('operational_year');
        if ($opYear) {
            return DB::table('operational_years')->where('year', $opYear)->where('is_archived', true)->exists();
        }

        return false;
    }

    /**
     * Check if an operational year is archived by directly querying the database.
     */
    public static function isYearArchived(int|OperationalYear $year): bool
    {
        if ($year instanceof OperationalYear) {
            return (bool) DB::table('operational_years')->where('id', $year->id)->value('is_archived');
        }

        if ($year > 1900 && $year < 3000) {
            return (bool) DB::table('operational_years')->where('year', $year)->value('is_archived');
        }

        return (bool) DB::table('operational_years')->where('id', $year)->value('is_archived');
    }

    /**
     * Check if a sheet belongs to an archived workspace by directly querying the database.
     */
    public static function isSheetArchived(int|SpreadsheetSheet $sheet): bool
    {
        $sheetId = $sheet instanceof SpreadsheetSheet ? $sheet->id : (int) $sheet;
        $sheetRecord = DB::table('spreadsheet_sheets')->where('id', $sheetId)->first();
        if (!$sheetRecord) {
            return false;
        }

        if ($sheetRecord->monthly_workspace_id) {
            $isArchived = DB::table('monthly_workspaces')
                ->join('operational_years', 'monthly_workspaces.year_id', '=', 'operational_years.id')
                ->where('monthly_workspaces.id', $sheetRecord->monthly_workspace_id)
                ->where('operational_years.is_archived', true)
                ->exists();

            if ($isArchived) {
                return true;
            }
        }

        if ($sheetRecord->group_id) {
            return self::isGroupArchived((int) $sheetRecord->group_id);
        }

        return false;
    }

    /**
     * Validate that an Admin user is not attempting to access an archived workspace.
     * Aborts with HTTP 403 if restricted.
     */
    public static function validateAdminWorkspaceAccess(?User $user, int|FisheriesGroup|null $group): void
    {
        if (!$user || !$group || $user->isOwner()) {
            return;
        }

        if ($user->isAdmin() && self::isGroupArchived($group)) {
            abort(403, 'Workspace ini telah diarsipkan dan tidak lagi dapat diakses.');
        }
    }

    /**
     * Validate that an Admin user is not attempting to access an archived operational year.
     * Aborts with HTTP 403 if restricted.
     */
    public static function validateAdminYearAccess(?User $user, int|OperationalYear|null $year): void
    {
        if (!$user || !$year || $user->isOwner()) {
            return;
        }

        if ($user->isAdmin() && self::isYearArchived($year)) {
            abort(403, 'Workspace tahun ini telah diarsipkan dan tidak dapat diakses oleh Admin.');
        }
    }

    /**
     * Validate that an Admin user is not attempting to access an archived spreadsheet sheet.
     * Aborts with HTTP 403 if restricted.
     */
    public static function validateAdminSheetAccess(?User $user, int|SpreadsheetSheet|null $sheet): void
    {
        if (!$user || !$sheet || $user->isOwner()) {
            return;
        }

        if ($user->isAdmin() && self::isSheetArchived($sheet)) {
            abort(403, 'Workspace ini telah diarsipkan dan tidak lagi dapat diakses.');
        }
    }
}

