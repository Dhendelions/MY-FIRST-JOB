<?php

namespace App\Http\Middleware;

use App\Models\FisheriesGroup;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetSheet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $group = $this->resolveGroup($request);
        if (!$group) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user->isOwner() && $group->isArchived()) {
            abort(403, 'Workspace ini telah diarsipkan dan tidak dapat diakses oleh Admin.');
        }

        if (in_array($request->route()?->getName(), ['group.workspace', 'groups.unlock'], true)) {
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
            abort(403, 'Masukkan password workspace sebelum mengakses datanya.');
        }

        return $next($request);
    }

    private function resolveGroup(Request $request): ?FisheriesGroup
    {
        $route = $request->route();

        $group = $route?->parameter('group');
        if ($group instanceof FisheriesGroup) {
            return $group;
        }
        if (is_numeric($group)) {
            return FisheriesGroup::find((int) $group);
        }

        $sheet = $route?->parameter('sheet');
        if ($sheet instanceof SpreadsheetSheet) {
            return $sheet->group;
        }
        if (is_numeric($sheet)) {
            return SpreadsheetSheet::with('group')->find((int) $sheet)?->group;
        }

        $row = $route?->parameter('row');
        if (is_numeric($row)) {
            return SpreadsheetRow::with('sheet.group')->find((int) $row)?->sheet?->group;
        }

        $column = $route?->parameter('column');
        if (is_numeric($column)) {
            return SpreadsheetColumn::with('sheet.group')->find((int) $column)?->sheet?->group;
        }

        $rowId = $request->input('row_id');
        if (is_numeric($rowId)) {
            return SpreadsheetRow::with('sheet.group')->find((int) $rowId)?->sheet?->group;
        }

        $sheetId = $request->input('sheet_id');
        if (is_numeric($sheetId)) {
            return SpreadsheetSheet::with('group')->find((int) $sheetId)?->group;
        }

        return null;
    }
}
