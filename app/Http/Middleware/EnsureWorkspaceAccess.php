<?php

namespace App\Http\Middleware;

use App\Models\FisheriesGroup;
use App\Models\MonthlyWorkspace;
use App\Models\OperationalYear;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetSheet;
use App\Services\WorkspaceSecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Check Year-level routes (e.g. /company/year/{year}, /company/year/{year}/unlock)
        $yearParam = $request->route('year');
        if ($yearParam) {
            $yearNum = $yearParam instanceof OperationalYear ? $yearParam->year : (int) $yearParam;
            if ($yearNum > 0 && WorkspaceSecurityService::isYearArchived($yearNum)) {
                if ($user && $user->isAdmin()) {
                    abort(403, 'Ruang kerja tahun ini telah diarsipkan dan tidak dapat diakses oleh Admin.');
                }
            }
        }

        // 2. Resolve Workspace Group
        $group = $this->resolveGroup($request);
        if (!$group) {
            return $next($request);
        }

        // 3. Strict Archive Protection for Admins: All access rejected
        if ($user && $user->isAdmin() && WorkspaceSecurityService::isGroupArchived($group)) {
            abort(403, 'Ruang kerja ini telah diarsipkan dan tidak lagi dapat diakses.');
        }

        if (in_array($request->route()?->getName(), [
            'group.workspace',
            'groups.unlock',
            'company.year',
            'company.year.unlock',
            'groups.leave',
        ], true)) {
            return $next($request);
        }

        if (
            $request->route()?->getName() === 'groups.update'
            && !$group->hasPassword()
            && $group->isLeader((int) $user->id)
        ) {
            return $next($request);
        }

        if (!$group->isUnlockedForSession()) {
            // A browser request to an annual month is a navigation request,
            // not a data endpoint. Send it to the canonical year gate so the
            // global password/configuration modal can be shown. All sheet/API
            // endpoints remain hard 403 protected below.
            if ($request->route()?->getName() === 'company.workspace') {
                $year = $group->operationalYearModel();
                if ($year) {
                    return redirect()->route('company.year', $year->year);
                }
            }

            abort(403, 'Masukkan password ruang kerja sebelum mengakses datanya.');
        }

        return $next($request);
    }

    private function resolveGroup(Request $request): ?FisheriesGroup
    {
        $route = $request->route();

        // 1. Direct group parameter
        $group = $route?->parameter('group');
        if ($group instanceof FisheriesGroup) {
            return $group;
        }
        if (is_numeric($group)) {
            return FisheriesGroup::find((int) $group);
        }

        // 2. Company Workspace hierarchy: {year}/{month}
        $year = $route?->parameter('year');
        $month = $route?->parameter('month');
        if ($year && $month) {
            $opYear = OperationalYear::where('year', (int) $year)->first();
            if ($opYear) {
                $mw = MonthlyWorkspace::where('year_id', $opYear->id)->where('month', (int) $month)->first();
                if ($mw && $mw->group) {
                    return $mw->group;
                }
            }
        }

        // 3. Sheet parameter
        $sheet = $route?->parameter('sheet');
        if ($sheet instanceof SpreadsheetSheet) {
            return $sheet->group;
        }
        if (is_numeric($sheet)) {
            return SpreadsheetSheet::with('group')->find((int) $sheet)?->group;
        }

        // 4. Row parameter
        $row = $route?->parameter('row');
        if ($row instanceof SpreadsheetRow) {
            return $row->sheet?->group;
        }
        if (is_numeric($row)) {
            return SpreadsheetRow::with('sheet.group')->find((int) $row)?->sheet?->group;
        }

        // 5. Column parameter
        $column = $route?->parameter('column');
        if ($column instanceof SpreadsheetColumn) {
            return $column->sheet?->group;
        }
        if (is_numeric($column)) {
            return SpreadsheetColumn::with('sheet.group')->find((int) $column)?->sheet?->group;
        }

        // 6. Request body / input attributes
        $rowId = $request->input('row_id');
        if (is_numeric($rowId)) {
            return SpreadsheetRow::with('sheet.group')->find((int) $rowId)?->sheet?->group;
        }

        $sheetId = $request->input('sheet_id');
        if (is_numeric($sheetId)) {
            return SpreadsheetSheet::with('group')->find((int) $sheetId)?->group;
        }

        $groupId = $request->input('group_id');
        if (is_numeric($groupId)) {
            return FisheriesGroup::find((int) $groupId);
        }

        return null;
    }
}
