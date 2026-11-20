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

        // Returning to dashboard resets all workspace and year session authentications
        if ($routeName === 'dashboard') {
            $this->clearAllAuthentication($request);
            $request->session()->save();
            return $next($request);
        }

        // Utility and benign global requests must not disturb active workspace authentication
        if (in_array($routeName, [
            'livewire.update',
            'users.profile',
            'notifications.read-all',
            'friends.search',
            'friends.list',
            'friends.store',
            'friends.accept',
            'friends.remove',
        ], true)) {
            return $next($request);
        }

        $targetYear = $this->resolveTargetYear($request);
        $targetGroup = $this->resolveTargetGroup($request);

        // 1. Maintain Multi-Year Workspace Authentication
        $authenticatedYears = $request->session()->get('admin_authenticated_years', []);
        if (is_array($authenticatedYears) && $user) {
            $checkYear = $targetYear ?: ($targetGroup ? $targetGroup->operationalYearModel() : null);
            if ($checkYear) {
                $yearKey = (int) $checkYear->year;
                $yearIdKey = (int) $checkYear->id;
                $stored = $authenticatedYears[$yearKey] ?? $authenticatedYears[$yearIdKey] ?? null;

                if (is_array($stored)) {
                    // Check if password version or user mismatch
                    if (
                        (int) ($stored['user_id'] ?? 0) !== (int) $user->id ||
                        (int) ($stored['password_version'] ?? 1) !== (int) ($checkYear->password_version ?? 1)
                    ) {
                        // Invalidate this specific year
                        unset($authenticatedYears[$yearKey], $authenticatedYears[$yearIdKey]);
                        $request->session()->put('admin_authenticated_years', $authenticatedYears);
                        if ((int) ($request->session()->get('admin_year_auth.year_id') ?? 0) === (int) $checkYear->id) {
                            $request->session()->forget('admin_year_auth');
                        }
                        $request->session()->save();
                    } else {
                        // Keep current active context synchronized
                        $request->session()->put('admin_year_auth', [
                            'user_id' => $user->id,
                            'year_id' => $checkYear->id,
                            'password_version' => (int) ($checkYear->password_version ?? 1),
                        ]);
                    }
                }
            }
        }

        // 2. Maintain Standalone Workspace Authentication
        $auth = $request->session()->get('admin_workspace_auth');
        if (is_array($auth) && (int) ($auth['user_id'] ?? 0) === (int) ($user?->id ?? 0)) {
            if ($targetGroup && (int) $targetGroup->id === (int) ($auth['group_id'] ?? 0)) {
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

        return $next($request);
    }

    private function resolveTargetYear(Request $request): ?OperationalYear
    {
        $route = $request->route();
        $year = $route?->parameter('year');
        if ($year instanceof OperationalYear) {
            return $year;
        }
        if (is_numeric($year)) {
            $opYear = OperationalYear::where('year', (int) $year)->first();
            if ($opYear) {
                return $opYear;
            }
        }

        $targetGroup = $this->resolveTargetGroup($request);
        if ($targetGroup) {
            return $targetGroup->operationalYearModel();
        }

        return null;
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
        $request->session()->save();
    }

    private function clearYearAuthentication(Request $request): void
    {
        $request->session()->forget([
            'admin_year_auth',
            'admin_authenticated_years',
        ]);

        foreach (array_keys($request->session()->all()) as $key) {
            if (str_starts_with($key, 'year_unlocked_') || str_starts_with($key, 'admin_year_auth_')) {
                $request->session()->forget($key);
            }
        }
        $request->session()->save();
    }
}
