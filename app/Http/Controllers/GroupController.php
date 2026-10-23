<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
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
            $activeSheet = $sheets->first();
        }

        $requiresPasswordUnlock = $group->hasPassword()
            && !$request->session()->get('sheet_unlocked_' . $activeSheet->id, false);

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
            'requiresPasswordUnlock'
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

        if (!$group->verifyPassword($validated['password'])) {
            return back()->with('error', 'Password workspace salah.');
        }

        $sheet = $group->sheets()->findOrFail($validated['sheet_id']);
        $request->session()->put('sheet_unlocked_' . $sheet->id, true);

        return redirect()->route('group.workspace', [$group->id, $sheet->id]);
    }

    /**
     * Add member to the workspace via modal.
     * Leaders and Admins can invite members.
     */
    public function addMember(Request $request, FisheriesGroup $group)
    {
        $user = Auth::user();
        Gate::authorize('addMember', $group);

        $validated = $request->validate([
            'email_or_phone' => ['required', 'string'],
            'role' => ['required', 'string', 'in:leader,admin,karyawan'],
        ], [
            'email_or_phone.required' => 'Email atau nomor kontak pengguna wajib diisi.',
            'role.required' => 'Pilih peran anggota yang akan ditambahkan.',
            'role.in' => 'Peran yang dipilih tidak valid.',
        ]);

        $currentUserRole = $group->getMemberRole($user->id);
        if ($currentUserRole === 'admin' && $validated['role'] === 'leader') {
            abort(403, 'Administrator tidak memiliki wewenang untuk menunjuk Leader.');
        }

        $query = trim($validated['email_or_phone']);
        $targetUser = User::where('email', $query)
            ->orWhere('whatsapp', $query)
            ->first();

        if (!$targetUser) {
            return back()->with('error', "Pengguna dengan kontak '{$query}' belum terdaftar di sistem Dadhen Mandiri.");
        }

        $existing = GroupMember::where('group_id', $group->id)->where('user_id', $targetUser->id)->first();
        if ($existing) {
            $existing->update(['role' => $validated['role'], 'status' => 'Aktif']);
            return back()->with('success', "Peran {$targetUser->name} berhasil diperbarui menjadi {$validated['role']}.");
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $targetUser->id,
            'role' => $validated['role'],
            'status' => 'Aktif',
            'joined_at' => now(),
        ]);

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

        $member->update(['role' => $validated['role']]);

        return back()->with('success', "Peran anggota berhasil diperbarui menjadi {$validated['role']}.");
    }

    /**
     * Remove a member from the workspace.
     */
    public function removeMember(Request $request, FisheriesGroup $group, GroupMember $member)
    {
        Gate::authorize('removeMember', [$group, $member]);

        $name = $member->user ? $member->user->name : 'Anggota';
        $member->delete();

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
        ]);

        $updateData = [
            'name' => trim($validated['name']),
        ];

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

        return back()->with('success', "Data workspace {$group->name} berhasil diperbarui.");
    }

    /**
     * Delete Workspace (Leader only).
     */
    public function destroy(Request $request, FisheriesGroup $group)
    {
        Gate::authorize('delete', $group);

        $groupName = $group->name;
        $group->delete();

        return redirect()->route('dashboard')->with('success', "Workspace '{$groupName}' dan seluruh dokumennya telah berhasil dihapus.");
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
            $sheet = $group->sheets()->create([
                'name' => $validated['name'],
                'category' => 'custom',
            ]);
            $sheet->initializeBlankGrid();

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
            $sheet->update(['name' => $validated['name']]);
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
        $sheet->delete();

        $nextSheet = $group->sheets()->first();

        return redirect()->route('group.workspace', [$group->id, $nextSheet->id])
            ->with('success', "Lembar kerja '{$sheetName}' telah berhasil dihapus.");
    }
}
