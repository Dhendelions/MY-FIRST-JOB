<?php

namespace App\Http\Middleware;

use App\Models\FisheriesGroup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAdminReauthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $isLogoutRequest = $request->route()?->getName() === 'logout' && $request->isMethod('POST');
        if (!$isLogoutRequest && (!$request->isMethod('GET') || $request->expectsJson())) {
            return $next($request);
        }

        if (!$isLogoutRequest && in_array($request->route()?->getName(), [
            'spreadsheet.export',
            'friends.list',
            'friends.search',
            'groups.users.search',
        ], true)) {
            return $next($request);
        }

        $context = $request->session()->get('admin_workspace_context') ?? $request->session()->get('workspace_exit_context');
        if (!is_array($context) || (int) ($context['user_id'] ?? 0) !== (int) $request->user()->id) {
            return $next($request);
        }

        $group = FisheriesGroup::find($context['group_id'] ?? null);
        $role = $group?->getMemberRole($request->user()->id);
        if (!$group || !in_array($role, ['admin', 'karyawan'], true)) {
            $request->session()->forget(['admin_workspace_context', 'workspace_exit_context']);
            return $next($request);
        }

        $route = $request->route();
        $requestedGroup = $route?->parameter('group');
        $requestedGroupId = $requestedGroup instanceof FisheriesGroup
            ? $requestedGroup->id
            : (int) $requestedGroup;

        if ($route?->getName() === 'group.workspace' && $requestedGroupId === $group->id) {
            return $next($request);
        }

        $sheetId = (int) ($context['sheet_id'] ?? 0);
        $sheet = $group->sheets()->find($sheetId) ?? $group->sheets()->first();
        $destination = $isLogoutRequest ? 'logout' : $request->getRequestUri();

        $request->session()->flash('workspace_exit_target', $destination);

        return redirect()->route('group.workspace', $sheet
            ? [$group->id, $sheet->id]
            : [$group->id]);
    }
}
