<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\SpreadsheetSheet;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetCell;
use App\Models\User;
use App\Models\Notification;
use App\Services\SpreadsheetFormulaReferenceRewriter;
use App\Services\SpreadsheetWorkbookCalculator;
use App\Services\SpreadsheetActivityRecorder;

class GroupController extends Controller
{
    /**
     * Group / Team Workspace View with Multi-sheet Tabs + Excel Grid.
     * Enforces strict workspace membership authorization.
     */
    public function workspace(Request $request, FisheriesGroup $group, ?SpreadsheetSheet $sheet = null)
    {
        $user = Auth::user();

        // 1. Strict Authorization via Policy
        if (!$group->isMember($user->id)) {
            abort(403, 'Akses ditolak: Anda bukan anggota resmi dari workspace ini.');
        }

        $userRole = $group->getMemberRole($user->id);

        // Workspace lama tanpa password hanya dapat dikonfigurasi oleh pemilik
        // sebelum isinya dibuka. Modal akan langsung ditampilkan di halaman.
        $requiresPasswordSetup = !$group->hasPassword() && $userRole === 'leader';

        // 2. Load all sheets for this workspace
        $sheets = $group->sheets()->get();

        // If no sheets exist for any reason, initialize standard general-purpose sheets
        if ($sheets->isEmpty()) {
            $group->initializeStandardSheets();
            $sheets = $group->sheets()->get();
        }

        // 3. Determine active sheet
        $activeSheet = null;
        if ($sheet && $sheet->group_id === $group->id) {
            $activeSheet = $sheet;
        } elseif ($request->filled('sheet_id')) {
            $activeSheet = $sheets->firstWhere('id', (int) $request->query('sheet_id'));
        } elseif ($request->filled('category')) {
            $activeSheet = $sheets->firstWhere('category', $request->query('category'));
        }

        if (!$activeSheet) {
            $activeSheet = $sheets->firstWhere(fn ($s) => !$s->isRestrictedForRole($userRole)) ?? $sheets->first();
        }

        $isSheetRestricted = $activeSheet ? $activeSheet->isRestrictedForRole($userRole) : false;

        $requiresAdminAccountVerification = $userRole === 'admin'
            && !$group->isAdminSheetVerifiedForSession($user->id, $activeSheet?->id ?? 0);
        $requiresPasswordUnlock = $requiresAdminAccountVerification
            || ($userRole !== 'admin' && $group->hasPassword() && !$group->isUnlockedForSession());

        $canAccessWorkspace = ($userRole === 'admin' && !$requiresAdminAccountVerification)
            || ($userRole === 'karyawan' && (!$group->hasPassword() || $group->isUnlockedForSession()));

        if (in_array($userRole, ['admin', 'karyawan'], true) && $canAccessWorkspace && $activeSheet) {
            $context = [
                'user_id' => $user->id,
                'group_id' => $group->id,
                'sheet_id' => $activeSheet->id,
                'role' => $userRole,
            ];
            $request->session()->put('admin_workspace_context', $context);
            $request->session()->put('workspace_exit_context', $context);
        }

        // 4. Load members from database with normalized roles
        $members = $group->members()->with('user')->orderBy('joined_at')->get();

        // 5. User's joined workspaces for sidebar switcher
        $userGroups = $user->groups()->with('sheets')->get();

        return view('groups.workspace', compact(
            'group',
            'sheets',
            'activeSheet',
            'members',
            'userRole',
            'userGroups',
            'requiresPasswordSetup',
            'requiresPasswordUnlock',
            'requiresAdminAccountVerification',
            'isSheetRestricted'
        ));
    }

    /** Verify a workspace password for the current login session. */
    public function unlock(Request $request, FisheriesGroup $group)
    {
        abort_unless($group->isMember(Auth::id()), 403);

        $validated = $request->validate([
            'password' => ['required', 'string', 'max:100'],
            'sheet_id' => ['required', 'integer'],
        ]);

        $sheet = $group->sheets()->findOrFail($validated['sheet_id']);
        $user = Auth::user();
        $role = $group->getMemberRole($user->id);

        if ($role === 'admin') {
            if (!Hash::check($validated['password'], $user->password)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Password akun salah. Silakan coba lagi.'], 422);
                }

                return back()->with('error', 'Password akun salah. Silakan coba lagi.');
            }

            // One verified target only: returning to another sheet requires a
            // fresh account-password check, while a normal refresh does not.
            $group->markAdminSheetVerifiedForSession($user->id, $sheet->id);
        } else {
            if (!$group->verifyPassword($validated['password'])) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Password workspace salah.'], 422);
                }

                return back()->with('error', 'Password workspace salah.');
            }
            $request->session()->put('workspace_unlocked_' . $group->id, true);
            $request->session()->put('sheet_unlocked_' . $sheet->id, true);
        }

        if (in_array($role, ['admin', 'karyawan'], true)) {
            $context = [
                'user_id' => $user->id,
                'group_id' => $group->id,
                'sheet_id' => $sheet->id,
                'role' => $role,
            ];
            $request->session()->put('admin_workspace_context', $context);
            $request->session()->put('workspace_exit_context', $context);
        }

        $redirectUrl = route('group.workspace', [$group->id, $sheet->id]);
        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirectUrl]);
        }

        return redirect()->to($redirectUrl);
    }

    /** Verify the account password before leaving the active workspace. */
    public function leaveWorkspace(Request $request, FisheriesGroup $group)
    {
        $context = $request->session()->get('admin_workspace_context') ?? $request->session()->get('workspace_exit_context');
        $userRole = $group->getMemberRole(Auth::id());
        abort_unless(
            is_array($context)
                && (int) ($context['user_id'] ?? 0) === (int) Auth::id()
                && (int) ($context['group_id'] ?? 0) === $group->id
                && in_array($userRole, ['admin', 'karyawan'], true),
            403,
            'Verifikasi keluar tidak berlaku untuk workspace ini.'
        );

        $validated = $request->validate([
            'password' => ['required', 'string', 'max:100'],
            'destination' => ['required', 'string', 'max:2048'],
        ]);

        $destination = $validated['destination'];
        $isLogout = $destination === 'logout';
        $parsedDestination = parse_url($destination);
        $decodedPath = is_array($parsedDestination)
            ? rawurldecode($parsedDestination['path'] ?? '')
            : '';
        abort_unless(
            $isLogout || (
                is_array($parsedDestination)
                && !isset($parsedDestination['scheme'])
                && !isset($parsedDestination['host'])
                && str_starts_with($decodedPath, '/')
                && !str_starts_with($decodedPath, '//')
                && !str_contains($decodedPath, '\\')
                && !preg_match('/[\x00-\x1F\x7F]/', $decodedPath)
            ),
            422,
            'Tujuan navigasi tidak valid.'
        );

        $request->session()->flash('workspace_exit_target', $destination);
        if (!Hash::check($validated['password'], Auth::user()->password)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Password akun salah. Anda tetap berada di workspace.'], 422);
            }

            return back()->with('error', 'Password akun salah. Anda tetap berada di workspace.');
        }

        $request->session()->forget([
            $group->adminVerificationSessionKey(Auth::id()),
            'admin_workspace_context',
            'workspace_exit_context',
            'workspace_exit_target',
            'workspace_unlocked_' . $group->id,
        ]);
        foreach ($group->sheets()->pluck('id') as $sheetId) {
            $request->session()->forget('sheet_unlocked_' . $sheetId);
        }

        if ($isLogout) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['redirect' => route('login')]);
            }

            return redirect()->route('login');
        }

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $destination]);
        }

        return redirect()->to($destination);
    }

    /**
     * Search registered users by name for adding members to the workspace.
     */
    public function searchUsers(Request $request, FisheriesGroup $group)
    {
        abort_unless($group->isMember(Auth::id()), 403);

        $name = trim($request->query('name', ''));
        if (mb_strlen($name) < 1) {
            return response()->json(['users' => []]);
        }

        $activeMemberUserIds = $group->members()->where('status', 'Aktif')->pluck('user_id')->all();

        $users = User::where('name', 'like', "%{$name}%")
            ->take(12)
            ->get(['id', 'name', 'role_title', 'avatar']);

        $data = $users->map(function ($u) use ($activeMemberUserIds) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'role_title' => $u->role_title ?? 'Pengguna Terdaftar',
                'avatar_url' => $u->avatar_url,
                'is_member' => in_array($u->id, $activeMemberUserIds, true),
            ];
        });

        return response()->json(['users' => $data]);
    }

    /**
     * Add member to the workspace via modal.
     * Uses system registered user and role.
     * Leaders and Admins can invite members.
     */
    public function addMember(Request $request, FisheriesGroup $group)
    {
        $user = Auth::user();
        Gate::authorize('addMember', $group);

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'email_or_phone' => ['nullable', 'string'],
            'role' => ['required', 'string', 'in:leader,admin,karyawan'],
        ], [
            'role.required' => 'Pilih peran anggota yang akan ditambahkan.',
            'role.in' => 'Peran yang dipilih tidak valid.',
        ]);

        $currentUserRole = $group->getMemberRole($user->id);
        if ($currentUserRole === 'admin' && $validated['role'] === 'leader') {
            abort(403, 'Administrator tidak memiliki wewenang untuk menunjuk Leader.');
        }

        $targetUser = null;
        if (!empty($validated['user_id'])) {
            $targetUser = User::find($validated['user_id']);
        } elseif (!empty($validated['email_or_phone'])) {
            $query = trim($validated['email_or_phone']);
            $targetUser = User::where('name', $query)
                ->orWhere('email', $query)
                ->orWhere('whatsapp', $query)
                ->first();
        }

        if (!$targetUser) {
            return back()->with('error', 'Pilih akun pengguna yang valid dari daftar pencarian.');
        }

        $existing = GroupMember::where('group_id', $group->id)->where('user_id', $targetUser->id)->first();
        if ($existing) {
            if ($existing->status === 'Aktif') {
                return back()->with('error', "Pengguna '{$targetUser->name}' sudah menjadi anggota di workspace ini.");
            }
            $existing->update(['role' => $validated['role'], 'status' => 'Aktif']);
            app(SpreadsheetActivityRecorder::class)->recordWorkspace(
                $user,
                $group,
                'member_added',
                null,
                $targetUser->name . ' (' . ucfirst($validated['role']) . ')',
                ['user_name' => $targetUser->name, 'role' => $validated['role'], 'user_id' => $targetUser->id]
            );
            return back()->with('success', "Anggota {$targetUser->name} berhasil diaktifkan kembali dengan peran {$validated['role']}.");
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $targetUser->id,
            'role' => $validated['role'],
            'status' => 'Aktif',
            'joined_at' => now(),
        ]);

        app(SpreadsheetActivityRecorder::class)->recordWorkspace(
            $user,
            $group,
            'member_added',
            null,
            $targetUser->name . ' (' . ucfirst($validated['role']) . ')',
            ['user_name' => $targetUser->name, 'role' => $validated['role'], 'user_id' => $targetUser->id]
        );

        Notification::create([
            'user_id' => $targetUser->id,
            'type' => 'group_invite',
            'title' => "Ditambahkan ke Workspace {$group->name}",
            'description' => "Anda telah didaftarkan sebagai {$validated['role']} di workspace '{$group->name}'.",
            'is_read' => false,
        ]);

        return back()->with('success', "{$targetUser->name} berhasil ditambahkan sebagai {$validated['role']}.");
    }

    /**
     * Update an existing member's role.
     */
    public function updateMemberRole(Request $request, FisheriesGroup $group, GroupMember $member)
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:leader,admin,karyawan'],
        ]);

        Gate::authorize('changeRole', [$group, $member, $validated['role']]);

        $oldRole = $member->role;
        $member->update(['role' => $validated['role']]);

        app(SpreadsheetActivityRecorder::class)->recordWorkspace(
            Auth::user(),
            $group,
            'role_changed',
            ucfirst($oldRole),
            ucfirst($validated['role']),
            ['target_user_name' => $member->user?->name, 'target_user_id' => $member->user_id, 'old_role' => $oldRole, 'new_role' => $validated['role']]
        );

        return back()->with('success', "Peran anggota berhasil diperbarui menjadi {$validated['role']}.");
    }

    /**
     * Remove a member from the workspace.
     */
    public function removeMember(Request $request, FisheriesGroup $group, GroupMember $member)
    {
        Gate::authorize('removeMember', [$group, $member]);

        $name = $member->user ? $member->user->name : 'Anggota';
        $oldRole = $member->role;
        $memberUserId = $member->user_id;
        $member->delete();

        app(SpreadsheetActivityRecorder::class)->recordWorkspace(
            Auth::user(),
            $group,
            'member_removed',
            $name . ' (' . ucfirst($oldRole) . ')',
            null,
            ['removed_user_name' => $name, 'removed_user_id' => $memberUserId, 'role' => $oldRole]
        );

        return back()->with('success', "{$name} telah dikeluarkan dari workspace.");
    }

    /**
     * Update Workspace Settings.
     */
    public function update(Request $request, FisheriesGroup $group)
    {
        Gate::authorize('update', $group);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $oldName = $group->name;
        $updateData = [
            'name' => trim($validated['name']),
        ];

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'ws_' . $group->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/workspaces');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $filename);
            $updateData['avatar'] = 'uploads/workspaces/' . $filename;
        }

        if (!empty($validated['password'])) {
            $request->validate(['current_password' => ['required', 'string']]);
            if (!$group->verifyPassword($request->input('current_password'))) {
                return back()->with('error', 'Password saat ini tidak sesuai.');
            }
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $group->update($updateData);
        foreach ($group->sheets()->pluck('id') as $sheetId) {
            $request->session()->forget('sheet_unlocked_' . $sheetId);
        }

        app(SpreadsheetActivityRecorder::class)->recordWorkspace(
            Auth::user(),
            $group,
            'workspace_updated',
            $oldName,
            $group->name,
            ['name' => $group->name, 'has_avatar' => !empty($updateData['avatar'])]
        );

        return back()->with('success', "Data workspace {$group->name} berhasil diperbarui.");
    }

    /**
     * Delete Workspace (Leader only).
     */
    public function destroy(Request $request, FisheriesGroup $group)
    {
        Gate::authorize('delete', $group);

        $deletedWorkspace = DB::transaction(function () use ($group) {
            $lockedGroup = FisheriesGroup::query()->lockForUpdate()->findOrFail($group->id);
            Gate::authorize('delete', $lockedGroup);
            $workspace = [
                'name' => $lockedGroup->name,
                'avatar_path' => $this->workspaceAvatarFilePath($lockedGroup),
            ];
            $lockedGroup->delete();

            return $workspace;
        });
        $avatarCleanupFailed = !$this->deleteWorkspaceAvatarFile($deletedWorkspace['avatar_path']);

        $message = "Workspace '{$deletedWorkspace['name']}' dan seluruh dokumennya telah berhasil dihapus.";
        if ($avatarCleanupFailed) {
            $message .= ' File avatar workspace gagal dibersihkan.';
        }

        return redirect()->route('dashboard')->with('success', $message);
    }

    /** Delete selected workspaces only when every requested workspace is manageable. */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'groups' => ['required', 'array', 'min:1', 'max:100'],
            'groups.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);

        $groups = FisheriesGroup::query()->whereIn('id', $validated['groups'])->get()->keyBy('id');

        foreach ($groups as $group) {
            Gate::authorize('delete', $group);
        }

        $deleted = [];
        $failed = [];
        $cleanupWarnings = [];

        foreach ($validated['groups'] as $groupId) {
            $selectedGroup = $groups->get($groupId);
            if (!$selectedGroup) {
                $failed[] = "Workspace #{$groupId} (sudah tidak tersedia)";
                continue;
            }

            try {
                $workspace = DB::transaction(function () use ($groupId) {
                    $group = FisheriesGroup::query()->lockForUpdate()->findOrFail($groupId);
                    Gate::authorize('delete', $group);
                    $workspace = [
                        'name' => $group->name,
                        'avatar_path' => $this->workspaceAvatarFilePath($group),
                    ];
                    $group->delete();

                    return $workspace;
                });
                $deleted[] = $workspace['name'];
                if (!$this->deleteWorkspaceAvatarFile($workspace['avatar_path'])) {
                    $cleanupWarnings[] = $workspace['name'];
                }
            } catch (AuthorizationException) {
                $failed[] = $selectedGroup->name . ' (izin berubah sebelum penghapusan)';
            } catch (ModelNotFoundException) {
                $failed[] = $selectedGroup->name . ' (workspace sudah tidak tersedia)';
            } catch (QueryException $exception) {
                Log::warning('Workspace deletion failed due to a database constraint.', [
                    'workspace_id' => $groupId,
                    'user_id' => Auth::id(),
                    'exception' => $exception->getMessage(),
                ]);
                $failed[] = $selectedGroup->name . ' (data terkait tidak dapat dihapus dengan aman)';
            }
        }

        $message = count($deleted) . ' workspace berhasil dihapus';
        if ($deleted) {
            $message .= ': ' . implode(', ', $deleted) . '.';
        } else {
            $message = 'Tidak ada workspace yang berhasil dihapus.';
        }
        if ($failed) {
            $message .= ' Gagal menghapus: ' . implode(', ', $failed) . '.';
        }
        if ($cleanupWarnings) {
            $message .= ' Workspace terhapus, tetapi file avatar gagal dibersihkan: ' . implode(', ', $cleanupWarnings) . '.';
        }

        return redirect()->route('dashboard')
            ->with($failed ? 'error' : 'success', $message);
    }

    private function workspaceAvatarFilePath(FisheriesGroup $group): ?string
    {
        $avatar = str_replace('\\', '/', (string) $group->avatar);
        $prefix = 'uploads/workspaces/';
        if (!str_starts_with($avatar, $prefix)) {
            return null;
        }

        $filename = substr($avatar, strlen($prefix));
        $directory = realpath(public_path('uploads/workspaces'));
        $path = realpath(public_path($prefix . $filename));
        $publicDirectory = realpath(public_path());
        if (
            $filename === ''
            || basename($filename) !== $filename
            || !$directory
            || !$path
            || !$publicDirectory
            || !str_starts_with($directory, $publicDirectory . DIRECTORY_SEPARATOR)
            || is_link(public_path($prefix . $filename))
            || dirname($path) !== $directory
            || !is_file($path)
            || FisheriesGroup::query()->where('avatar', $group->avatar)->where('id', '!=', $group->id)->exists()
        ) {
            return null;
        }

        return $path;
    }

    private function deleteWorkspaceAvatarFile(?string $path): bool
    {
        if (!$path || !is_file($path)) {
            return true;
        }

        if (unlink($path)) {
            return true;
        }

        Log::warning('Workspace was deleted but its avatar file could not be removed.', [
            'path' => $path,
        ]);

        return false;
    }

    /**
     * Add a sheet to the workspace.
     */
    public function addSheet(Request $request, FisheriesGroup $group)
    {
        Gate::authorize('manageSheets', $group);
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:31',
                function ($attribute, $value, $fail) {
                    if (preg_match('~[\\\\/?*:\[\]]~u', $value)) {
                        $fail('Nama worksheet berisi karakter yang tidak didukung Excel.');
                    }
                },
                Rule::unique('spreadsheet_sheets', 'name')->where('group_id', $group->id),
            ],
        ]);

        $sheet = DB::transaction(function () use ($group, $validated) {
            $metadata = [
                'identity_color' => SpreadsheetSheet::cyclicIdentityColorKey($group->sheets()->count()),
            ];
            $sheet = $group->sheets()->create([
                'name' => $validated['name'],
                'category' => 'custom',
                'metadata' => $metadata,
            ]);
            $sheet->initializeBlankGrid();
            app(SpreadsheetActivityRecorder::class)->recordWorkspace(
                Auth::user(),
                $group,
                'sheet_added',
                null,
                $sheet->name,
                [
                    'sheet_id' => $sheet->id,
                    'sheet_name' => $sheet->name,
                    'identity_color' => $metadata['identity_color'],
                ]
            );

            return $sheet;
        });

        return redirect()->route('group.workspace', [$group->id, $sheet->id])
            ->with('success', "Lembar kerja baru '{$sheet->name}' berhasil ditambahkan.");
    }

    /**
     * Rename sheet.
     */
    public function renameSheet(Request $request, SpreadsheetSheet $sheet)
    {
        Gate::authorize('manage', $sheet);
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:31',
                function ($attribute, $value, $fail) {
                    if (preg_match('~[\\\\/?*:\[\]]~u', $value)) {
                        $fail('Nama worksheet berisi karakter yang tidak didukung Excel.');
                    }
                },
                Rule::unique('spreadsheet_sheets', 'name')
                    ->where('group_id', $sheet->group_id)
                    ->ignore($sheet->id),
            ],
        ]);

        $oldName = $sheet->name;
        $group = $sheet->group;
        DB::transaction(function () use ($sheet, $group, $oldName, $validated) {
            $metadata = $sheet->metadata ?? [];
            $metadata['identity_color'] ??= $sheet->identityColorKey();
            $sheet->update(['name' => $validated['name'], 'metadata' => $metadata]);
            $sheetIds = $group->sheets()->pluck('id');
            $rewriter = app(SpreadsheetFormulaReferenceRewriter::class);

            SpreadsheetCell::whereNotNull('formula')
                ->whereHas('row', fn ($query) => $query->whereIn('sheet_id', $sheetIds))
                ->get()
                ->each(function (SpreadsheetCell $cell) use ($rewriter, $oldName, $validated) {
                    $formula = $rewriter->rewrite($cell->formula, [$oldName => $validated['name']]);
                    if ($formula === $cell->formula) {
                        return;
                    }

                    $cell->update([
                        'formula' => $formula,
                        'version' => DB::raw('version + 1'),
                    ]);
                });
        });
        app(SpreadsheetWorkbookCalculator::class)->recalculate($group);
        app(SpreadsheetActivityRecorder::class)->recordWorkspace(
            Auth::user(),
            $group,
            'sheet_renamed',
            $oldName,
            $sheet->name,
            ['sheet_id' => $sheet->id, 'sheet_name' => $sheet->name]
        );

        return back()->with('success', "Nama lembar kerja berhasil diubah menjadi '{$sheet->name}'.");
    }

    /**
     * Duplicate an existing sheet with columns, rows, and cells.
     */
    public function duplicateSheet(Request $request, SpreadsheetSheet $sheet)
    {
        Gate::authorize('manageSheets', $sheet->group);

        $newSheet = DB::transaction(function () use ($sheet) {
            $baseName = $sheet->name . ' (Salinan)';
            $name = $baseName;
            $suffix = 2;
            while ($sheet->group->sheets()->where('name', $name)->exists()) {
                $ending = ' (' . $suffix++ . ')';
                $name = mb_substr($baseName, 0, 31 - mb_strlen($ending)) . $ending;
            }
            $duplicated = $sheet->group->sheets()->create([
                'name' => $name,
                'category' => $sheet->category,
                'metadata' => $sheet->metadata,
            ]);

            $colMap = [];
            foreach ($sheet->columns as $col) {
                $newCol = $duplicated->columns()->create([
                    'letter' => $col->letter,
                    'title' => $col->title,
                    'width' => $col->width,
                    'sort_order' => $col->sort_order,
                ]);
                $colMap[$col->id] = $newCol->id;
            }

            foreach ($sheet->rows as $row) {
                $newRow = $duplicated->rows()->create([
                    'sort_order' => $row->sort_order,
                    'height' => $row->height,
                ]);

                foreach ($row->cells as $cell) {
                    if (isset($colMap[$cell->column_id])) {
                        SpreadsheetCell::create([
                            'row_id' => $newRow->id,
                            'column_id' => $colMap[$cell->column_id],
                            'value' => $cell->value,
                            'formula' => $cell->formula,
                            'format' => $cell->format,
                        ]);
                    }
                }
            }

            app(SpreadsheetActivityRecorder::class)->recordWorkspace(
                Auth::user(),
                $sheet->group,
                'sheet_duplicated',
                $sheet->name,
                $duplicated->name,
                ['sheet_id' => $duplicated->id, 'sheet_name' => $duplicated->name]
            );

            return $duplicated;
        });

        return redirect()->route('group.workspace', [$sheet->group_id, $newSheet->id])
            ->with('success', "Lembar kerja '{$sheet->name}' berhasil diduplikasi.");
    }

    /**
     * Delete sheet (cannot delete the last remaining sheet in a workspace).
     */
    public function deleteSheet(Request $request, SpreadsheetSheet $sheet)
    {
        Gate::authorize('manage', $sheet);

        $group = $sheet->group;
        if ($group->sheets()->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus lembar kerja terakhir dalam workspace.');
        }

        $sheetName = $sheet->name;
        app(SpreadsheetActivityRecorder::class)->recordWorkspace(
            Auth::user(),
            $group,
            'sheet_deleted',
            $sheetName,
            null,
            ['sheet_id' => $sheet->id, 'sheet_name' => $sheetName]
        );
        $sheet->delete();

        $nextSheet = $group->sheets()->first();

        return redirect()->route('group.workspace', [$group->id, $nextSheet->id])
            ->with('success', "Lembar kerja '{$sheetName}' telah berhasil dihapus.");
    }
}
