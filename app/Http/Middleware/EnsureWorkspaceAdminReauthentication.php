<?php

namespace App\Http\Middleware;

use App\Models\FisheriesGroup;
use App\Models\MonthlyWorkspace;
use App\Models\OperationalYear;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetSheet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAdminReauthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user) {
            $user->refresh();
            $currentRole = strtolower((string)($user->role ?? $user->role_title ?? 'admin'));
            $sessionRole = $request->session()->get('auth_user_role');

            if ($sessionRole !== null && $sessionRole !== $currentRole) {
                $this->clearAllAuthentication($request);
            }

            $request->session()->put('auth_user_role', $currentRole);
        }

        $routeName = $request->route()?->getName();
        if ($routeName === 'logout') {
            $this->clearAllAuthentication($request);
            return $next($request);
        }

        $auth = $request->session()->get('admin_workspace_auth');
        if (is_array($auth) && (int) ($auth['user_id'] ?? 0) === (int) ($user?->id ?? 0)) {
            if ($routeName === 'livewire.update') {
                return $next($request);
            }

            $targetGroup = $this->resolveTargetGroup($request);
            if ($targetGroup && (int) $targetGroup->id === (int) ($auth['group_id'] ?? 0)) {
                $this->clearYearAuthentication($request);
                return $next($request);
            }

            $this->clearWorkspaceAuthentication($request);
        } elseif (
            $auth !== null
            || $request->session()->has('admin_workspace_context')
            || $request->session()->has('workspace_exit_context')
            || $request->session()->has('workspace_exit_target')
        ) {
            $this->clearWorkspaceAuthentication($request);
        }

        $yearAuth = $request->session()->get('admin_year_auth');
        if (is_array($yearAuth) && (int) ($yearAuth['user_id'] ?? 0) === (int) ($user?->id ?? 0)) {
            $year = $request->route()?->parameter('year');
            $operationalYear = is_numeric($year)
                ? OperationalYear::where('year', (int) $year)->first()
                : null;

            if (
                in_array($routeName, ['company.year', 'company.year.unlock'], true)
                && $operationalYear
                && (int) $operationalYear->id === (int) ($yearAuth['year_id'] ?? 0)
            ) {
                return $next($request);
            }
        }

        $this->clearYearAuthentication($request);

        return $next($request);
    }

    private function resolveTargetGroup(Request $request): ?FisheriesGroup
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

        if ($route?->getName() === 'company.workspace') {
            $year = (int) $route->parameter('year');
            $month = (int) $route->parameter('month');
            $opYear = OperationalYear::where('year', $year)->first();
            $workspace = $opYear
                ? MonthlyWorkspace::where('year_id', $opYear->id)->where('month', $month)->first()
                : null;

            return $workspace?->group;
        }

        return null;
    }

    private function clearAllAuthentication(Request $request): void
    {
        $this->clearWorkspaceAuthentication($request);
        $this->clearYearAuthentication($request);
    }

    private function clearWorkspaceAuthentication(Request $request): void
    {
        $request->session()->forget([
            'admin_workspace_auth',
            'admin_workspace_context',
            'workspace_exit_context',
            'workspace_exit_target',
        ]);

        foreach (array_keys($request->session()->all()) as $key) {
            if (str_starts_with($key, 'workspace_unlocked_') || str_starts_with($key, 'sheet_unlocked_')) {
                $request->session()->forget($key);
            }
        }
    }

    private function clearYearAuthentication(Request $request): void
    {
        $request->session()->forget('admin_year_auth');

        foreach (array_keys($request->session()->all()) as $key) {
            if (str_starts_with($key, 'year_unlocked_')) {
                $request->session()->forget($key);
            }
        }
    }
}
