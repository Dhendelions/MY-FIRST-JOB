<?php

namespace App\Http\Controllers;

use App\Models\OperationalYear;
use App\Services\CompanyAnalyticsService;
use App\Services\CompanyWorkspaceInitializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyYearController extends Controller
{
    public function __construct(
        private CompanyAnalyticsService $analyticsService
    ) {
    }

    /**
     * Create a new Operational Year with 12 monthly workspaces and 60 spreadsheet sheets.
     * Only Company Owner can create an operational year.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isOwner()) {
            abort(403, 'Akses ditolak: Hanya Owner yang memiliki izin untuk membuat tahun operasional baru.');
        }

        $validated = $request->validate([
            'year' => [
                'required',
                'integer',
                'digits:4',
                'unique:operational_years,year',
            ],
        ], [
            'year.required' => 'Tahun operasional wajib diisi.',
            'year.integer' => 'Tahun harus berupa bilangan bulat.',
            'year.digits' => 'Tahun harus berupa format 4 digit angka.',
            'year.unique' => 'Tahun operasional :input sudah terdaftar.',
        ]);

        $yearNumber = (int) $validated['year'];
        $initializer = app(CompanyWorkspaceInitializer::class);
        $operationalYear = $initializer->provisionYear($yearNumber, $user->id, false);

        \App\Services\ActivityLogger::log('year_created', "Tahun operasional {$yearNumber} berhasil dibuat dengan 12 workspace bulanan.", $operationalYear, [
            'year' => $yearNumber,
            'created_by' => $user->name,
        ], $user);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Tahun operasional {$yearNumber} dan 12 workspace bulanan berhasil dibuat.",
                'data' => [
                    'year' => $operationalYear->year,
                    'is_active' => $operationalYear->is_active,
                    'is_archived' => $operationalYear->is_archived,
                    'monthly_workspaces_count' => $operationalYear->monthlyWorkspaces()->count(),
                ],
            ], 201);
        }

        return back()->with('success', "Tahun operasional {$yearNumber} berhasil dibuat dengan 12 workspace bulanan dan 60 lembar kerja.");
    }

    /**
     * Archive an active operational year.
     * Only Company Owner can archive an operational year.
     */
    public function archive(OperationalYear $year, Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isOwner()) {
            abort(403, 'Akses ditolak: Hanya Owner yang memiliki izin untuk mengarsipkan tahun operasional.');
        }

        $year->archive();

        \App\Services\ActivityLogger::log('year_archived', "Tahun operasional {$year->year} berhasil diarsipkan menjadi read-only.", $year, [
            'year' => $year->year,
            'archived_by' => $user->name,
        ], $user);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Tahun operasional {$year->year} berhasil diarsipkan (read-only).",
                'data' => [
                    'year' => $year->year,
                    'status' => 'archived',
                    'is_archived' => true,
                    'is_active' => false,
                ],
            ]);
        }

        return back()->with('success', "Tahun operasional {$year->year} berhasil diarsipkan. Semua lembar kerja di tahun ini kini bersifat read-only.");
    }

    /**
     * Restore an archived operational year to active status.
     * Only Company Owner can restore an operational year.
     */
    public function restore(OperationalYear $year, Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isOwner()) {
            abort(403, 'Akses ditolak: Hanya Owner yang memiliki izin untuk memulihkan tahun operasional.');
        }

        $year->restore();

        \App\Services\ActivityLogger::log('year_restored', "Tahun operasional {$year->year} dipulihkan menjadi aktif.", $year, [
            'year' => $year->year,
            'restored_by' => $user->name,
        ], $user);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Tahun operasional {$year->year} berhasil dipulihkan menjadi aktif.",
                'data' => [
                    'year' => $year->year,
                    'status' => 'active',
                    'is_archived' => false,
                    'is_active' => true,
                ],
            ]);
        }

        return back()->with('success', "Tahun operasional {$year->year} berhasil dipulihkan menjadi aktif. Pengeditan lembar kerja diizinkan kembali.");
    }

    /**
     * Company Analytics: Centralized company analysis module with 4 sub-modules:
     * 1. Overview
     * 2. Financial Analytics
     * 3. Operational Analytics
     * 4. Yearly Comparison
     *
     * Strictly restricted to Owner accounts.
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isOwner()) {
            abort(403, 'Akses ditolak: Hanya Owner yang memiliki wewenang untuk mengakses Company Analytics.');
        }

        $activeTab = $request->query('tab', 'overview');
        $validTabs = ['overview', 'financial', 'operational', 'comparison'];
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = 'overview';
        }

        $selectedYearVal = $request->has('year') ? (int) $request->query('year') : null;
        $overviewData = $this->analyticsService->getCompanyOverview($selectedYearVal);

        $allYears = $overviewData['all_years'];
        $selectedYear = $overviewData['selected_year'];

        // Detail data based on active tab
        $financialData = null;
        $operationalData = null;
        $comparisonData = null;

        if ($selectedYear) {
            if ($activeTab === 'financial' || $activeTab === 'overview') {
                $financialData = $this->analyticsService->getFinancialAnalytics($selectedYear);
            }
            if ($activeTab === 'operational' || $activeTab === 'overview') {
                $operationalData = $this->analyticsService->getOperationalAnalytics($selectedYear);
            }
        }

        if ($activeTab === 'comparison') {
            $comparisonData = $this->analyticsService->getYearlyComparison();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'active_tab' => $activeTab,
                'overview' => $overviewData,
                'financial' => $financialData,
                'operational' => $operationalData,
                'comparison' => $comparisonData,
            ]);
        }

        return view('company.analytics', compact(
            'user',
            'activeTab',
            'allYears',
            'selectedYear',
            'overviewData',
            'financialData',
            'operationalData',
            'comparisonData'
        ));
    }

    /**
     * Yearly Comparison endpoint (also accessible directly via URL /company/yearly-comparison).
     * Strictly restricted to Owner accounts.
     */
    public function yearlyComparison(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isOwner()) {
            abort(403, 'Akses ditolak: Hanya Owner yang memiliki wewenang untuk mengakses Perbandingan Tahunan.');
        }

        $comparisonData = $this->analyticsService->getYearlyComparison();
        $allYears = $comparisonData['all_years'];
        $comparisonList = $comparisonData['comparison'];

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'comparison' => $comparisonList,
            ]);
        }

        return view('company.comparison', compact('user', 'allYears', 'comparisonList'));
    }

    /**
     * Year-Based Workspace view (/company/year/{year}).
     * Displays all 12 monthly workspaces for the operational year.
     * Admin users must authenticate with the year password if configured.
     * Owner has unrestricted access.
     */
    public function show(Request $request, int $year)
    {
        $operationalYear = OperationalYear::with(['monthlyWorkspaces.group.sheets'])
            ->where('year', $year)
            ->firstOrFail();

        $user = Auth::user();
        abort_if($user->isAdmin() && $operationalYear->isArchived(), 403, 'Workspace tahun ini telah diarsipkan dan tidak dapat diakses oleh Admin.');
        $isUnlocked = $operationalYear->isUnlockedForUser($user?->id);

        return view('company.year', compact('operationalYear', 'isUnlocked', 'user'));
    }

    /**
     * Unlock a year workspace for the current session.
     */
    public function unlock(Request $request, int $year)
    {
        $operationalYear = OperationalYear::where('year', $year)->firstOrFail();
        $user = Auth::user();

        abort_if($user->isAdmin() && $operationalYear->isArchived(), 403, 'Workspace tahun ini telah diarsipkan dan tidak dapat diakses oleh Admin.');

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Password workspace wajib diisi.',
        ]);

        if (!$operationalYear->verifyPassword($validated['password'])) {
            $errorMsg = 'Password workspace tahun ' . $year . ' salah. Silakan coba lagi.';
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $errorMsg], 422);
            }
            return back()->with('error', $errorMsg);
        }

        $operationalYear->unlockForUser($user->id);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Workspace tahun ' . $year . ' berhasil dibuka.',
                'redirect' => route('company.year', $year),
            ]);
        }

        return redirect()->route('company.year', $year)->with('success', 'Workspace tahun ' . $year . ' berhasil dibuka.');
    }

    /**
     * Set or change the password for an operational year.
     * Only Owner can configure workspace passwords.
     * Updating or clearing password increments password_version, invalidating previous Admin sessions.
     */
    public function setPassword(Request $request, int $year)
    {
        $user = Auth::user();
        if (!$user || !$user->isOwner()) {
            abort(403, 'Akses ditolak: Hanya Owner yang memiliki wewenang untuk mengatur password workspace.');
        }

        $operationalYear = OperationalYear::where('year', $year)->firstOrFail();

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:4', 'confirmed'],
        ], [
            'password.min' => 'Password minimal harus 4 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if (empty($validated['password'])) {
            $operationalYear->password = null;
            $operationalYear->increment('password_version');
            $operationalYear->save();

            \App\Services\ActivityLogger::log('year_password_removed', "Password workspace tahun {$year} telah dihapus.", $operationalYear, [
                'year' => $year,
                'configured_by' => $user->name,
            ], $user);

            $message = "Password workspace tahun {$year} berhasil dihapus.";
        } else {
            $operationalYear->password = \Illuminate\Support\Facades\Hash::make($validated['password']);
            $operationalYear->increment('password_version');
            $operationalYear->save();

            \App\Services\ActivityLogger::log('year_password_updated', "Password workspace tahun {$year} berhasil diperbarui.", $operationalYear, [
                'year' => $year,
                'configured_by' => $user->name,
            ], $user);

            $message = "Password workspace tahun {$year} berhasil disimpan.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
