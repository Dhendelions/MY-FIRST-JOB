<?php

namespace App\Http\Controllers;

use App\Models\CompanyActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyActivityLogController extends Controller
{
    /**
     * Authorize that the current authenticated user is Company Owner.
     */
    protected function authorizeOwner(): User
    {
        $user = Auth::user();
        if (!$user || !$user->isOwner()) {
            abort(403, 'Akses ditolak: Hanya Owner yang dapat mengakses log aktivitas perusahaan.');
        }

        return $user;
    }

    /**
     * Display a listing of company activity logs.
     */
    public function index(Request $request)
    {
        $this->authorizeOwner();

        $query = CompanyActivityLog::with('actor');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('resource_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', trim($request->input('action')));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $actions = CompanyActivityLog::distinct()->pluck('action')->filter()->values();
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'role']);

        $stats = [
            'total_logs' => CompanyActivityLog::count(),
            'today_logs' => CompanyActivityLog::whereDate('created_at', now()->toDateString())->count(),
            'user_actions' => CompanyActivityLog::where('action', 'like', 'user_%')->orWhere('action', 'role_changed')->count(),
            'system_actions' => CompanyActivityLog::where('action', 'like', 'year_%')->count(),
        ];

        return view('company.activity_logs.index', compact('logs', 'actions', 'users', 'stats'));
    }
}

