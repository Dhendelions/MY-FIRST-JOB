<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\SpreadsheetSheet;
use App\Models\SpreadsheetCell;
use App\Models\Friendship;
use App\Models\User;
use App\Models\Notification;

class DashboardController extends Controller
{
    /**
     * Dashboard Overview View with Real-time KPIs & Workspace Catalog from Database.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Fetch user's joined workspaces from database
        $search = $request->query('q');

        $userGroupIds = $user->groups()->pluck('fisheries_groups.id');

        $groupsQuery = FisheriesGroup::with(['creator', 'activeMembers.user', 'sheets'])
            ->whereIn('id', $userGroupIds);

        if ($search) {
            $groupsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $myGroups = $groupsQuery->latest()->get();

        // 2. Real-Time KPI Statistics (Purely based on user's actual database records)
        $totalWorkspaces = $userGroupIds->count();
        $totalSheetsCount = SpreadsheetSheet::whereIn('group_id', $userGroupIds)->count();

        $sheetIds = SpreadsheetSheet::whereIn('group_id', $userGroupIds)->pluck('id');
        $totalRecordedCells = 0;
        if ($sheetIds->isNotEmpty()) {
            $totalRecordedCells = SpreadsheetCell::whereHas('row', function ($q) use ($sheetIds) {
                $q->whereIn('sheet_id', $sheetIds);
            })->whereNotNull('value')->where('value', '!=', '')->count();
        }

        $totalMembers = GroupMember::whereIn('group_id', $userGroupIds)->where('status', 'Aktif')->count();

        $totalFriends = Friendship::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('friend_id', $user->id);
        })->where('status', 'accepted')->count();

        // 3. User notifications from database
        $notifications = $user->notifications()->take(10)->get();
        $unreadCount = $user->unreadNotifications()->count();

        return view('dashboard', compact(
            'user',
            'myGroups',
            'totalWorkspaces',
            'totalSheetsCount',
            'totalRecordedCells',
            'totalMembers',
            'totalFriends',
            'notifications',
            'unreadCount',
            'search'
        ));
    }

    /**
     * Create a new Workspace.
     */
    /**
     * Create a new Workspace with Name and Password.
     * Category, pond_count, location, and description are given sensible defaults.
     */
    public function createGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:4', 'max:100'],
            'workspace_type' => ['nullable', 'in:manual,template'],
        ], [
            'name.required' => 'Nama workspace wajib diisi.',
            'password.required' => 'Password workspace wajib diisi.',
            'password.min' => 'Password workspace minimal 4 karakter.',
            'password.max' => 'Password workspace maksimal 100 karakter.',
        ]);

        $user = Auth::user();

        $group = DB::transaction(function () use ($validated, $user) {
            // Kolom lama ini tetap dipenuhi demi kompatibilitas database, tetapi
            // tidak lagi digunakan atau ditampilkan sebagai cara bergabung.
            $internalInviteCode = FisheriesGroup::generateInviteCode('Umum');
            $createdGroup = FisheriesGroup::create([
                'name' => trim($validated['name']),
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
                'category' => 'Umum',
                'location' => 'Utama',
                'pond_count' => 5,
                'description' => null,
                'invite_code' => $internalInviteCode,
                'created_by' => $user->id,
            ]);

            // Attach user as Leader
            GroupMember::create([
                'group_id' => $createdGroup->id,
                'user_id' => $user->id,
                'role' => 'leader',
                'status' => 'Aktif',
                'joined_at' => now(),
            ]);

            if (($validated['workspace_type'] ?? 'manual') === 'template') {
                app(\App\Services\OktAnalisaTemplateImporter::class)->import($createdGroup);
            } else {
                $createdGroup->initializeStandardSheets();
            }

            // Create persistent notification
            Notification::create([
                'user_id' => $user->id,
                'type' => 'success',
                'title' => "Workspace '{$createdGroup->name}' Berhasil Dibuat",
                'description' => 'Password telah diamankan. Lembar spreadsheet siap digunakan.',
                'is_read' => false,
            ]);

            return $createdGroup;
        });

        $request->session()->put('sheet_unlocked_' . $group->sheets()->first()->id, true);
        return redirect()->route('group.workspace', $group->id)
            ->with('success', "Workspace '{$group->name}' berhasil dibuat! Password tersimpan dengan aman.");
    }

    /**
     * Join existing Workspace using its name and workspace password.
     */
    public function joinGroup(Request $request)
    {
        $validated = $request->validate([
            'workspace_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:100'],
        ], [
            'workspace_name.required' => 'Nama workspace wajib dimasukkan.',
            'password.required' => 'Password workspace wajib dimasukkan.',
        ]);

        $user = Auth::user();
        $workspaceName = trim($validated['workspace_name']);
        $group = FisheriesGroup::where('name', $workspaceName)->first();

        if (!$group) {
            return back()->with('error', "Workspace '{$workspaceName}' tidak ditemukan. Periksa kembali namanya.");
        }

        $existing = GroupMember::where('group_id', $group->id)->where('user_id', $user->id)->first();
        if ($existing) {
            return redirect()->route('group.workspace', $group->id)->with('info', "Anda sudah menjadi anggota di workspace '{$group->name}'.");
        }

        // Verify Workspace Password
        if ($group->hasPassword()) {
            if (!$group->verifyPassword($validated['password'])) {
                return back()->with('error', 'Password workspace salah. Akses ditolak.');
            }
        } else {
            // Workspace lama yang belum diset password-nya: set password yang diinput jika belum ada
            if (!empty($validated['password'])) {
                $group->update(['password' => \Illuminate\Support\Facades\Hash::make($validated['password'])]);
            }
        }

        DB::transaction(function () use ($group, $user) {
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role' => 'karyawan',
                'status' => 'Aktif',
                'joined_at' => now(),
            ]);

            // Notify group creator & joined user
            Notification::create([
                'user_id' => $group->created_by,
                'type' => 'group_invite',
                'title' => 'Anggota Baru Bergabung',
                'description' => "{$user->name} telah bergabung ke workspace '{$group->name}' setelah verifikasi password berhasil.",
                'is_read' => false,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'type' => 'success',
                'title' => "Bergabung ke {$group->name}",
                'description' => "Password terverifikasi. Anda kini dapat mengakses dokumen spreadsheet '{$group->name}'.",
                'is_read' => false,
            ]);
        });

        // Password sudah diverifikasi pada proses bergabung ini; akses berikutnya
        // setelah logout/login tetap akan meminta password lagi.
        $request->session()->put('sheet_unlocked_' . $group->sheets()->first()->id, true);

        return redirect()->route('group.workspace', $group->id)->with('success', "Autentikasi berhasil! Selamat bergabung ke workspace {$group->name}.");
    }

    /**
     * Search friends by Name in the database.
     * Handles ambiguity and multiple results.
     */
    public function searchFriends(Request $request)
    {
        $name = trim($request->query('name', $request->input('name', '')));

        if (empty($name)) {
            return response()->json([
                'success' => true,
                'users' => [],
            ]);
        }

        $currentUser = Auth::user();

        $users = User::where('id', '!=', $currentUser->id)
            ->where('name', 'like', "%{$name}%")
            ->take(15)
            ->get(['id', 'name', 'role_title', 'avatar', 'created_at']);

        $results = $users->map(function ($u) use ($currentUser) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'role_title' => $u->role_title ?? 'Anggota Tim',
                'avatar_url' => $u->avatar_url,
                'status' => $currentUser->getFriendshipStatusWith($u->id),
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $results->count(),
            'users' => $results,
        ]);
    }

    /**
     * Get accepted friends and pending requests for the authenticated user.
     */
    public function getFriendsData()
    {
        $user = Auth::user();
        
        $accepted = $user->getAcceptedFriends()->map(function ($f) {
            return [
                'id' => $f->id,
                'name' => $f->name,
                'role_title' => $f->role_title ?? 'Anggota Tim',
                'avatar_url' => $f->avatar_url,
            ];
        });

        $pending = $user->getPendingReceivedRequests()->map(function ($pr) {
            return [
                'friendship_id' => $pr->id,
                'id' => $pr->user->id,
                'name' => $pr->user->name,
                'role_title' => $pr->user->role_title ?? 'Anggota Tim',
                'avatar_url' => $pr->user->avatar_url,
                'created_at' => $pr->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'friends' => $accepted,
            'pending' => $pending,
        ]);
    }

    /**
     * Connect with other colleagues by Name.
     * Handles single match, duplicate requests, existing friendship, and self-add.
     */
    public function addFriend(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'friend_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user = Auth::user();
        $target = null;

        // 1. Direct friend_id selection from search results
        if (!empty($validated['friend_id'])) {
            $target = User::find($validated['friend_id']);
        } 
        // 2. Or search by name
        elseif (!empty($validated['name'])) {
            $name = trim($validated['name']);
            $matches = User::where('id', '!=', $user->id)
                ->where('name', 'like', "%{$name}%")
                ->get();

            if ($matches->isEmpty()) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Pengguna dengan nama '{$name}' tidak ditemukan dalam database.",
                    ], 404);
                }
                return back()->with('error', "Pengguna dengan nama '{$name}' tidak ditemukan dalam database.");
            }

            if ($matches->count() > 1) {
                // Ambiguous: multiple users share similar name
                $formattedMatches = $matches->map(function ($u) use ($user) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'role_title' => $u->role_title ?? 'Anggota Tim',
                        'avatar_url' => $u->avatar_url,
                        'status' => $user->getFriendshipStatusWith($u->id),
                    ];
                });

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'ambiguous' => true,
                        'message' => "Ditemukan {$matches->count()} pengguna dengan nama yang cocok. Silakan pilih akun yang tepat.",
                        'users' => $formattedMatches,
                    ]);
                }

                return back()->with('info', "Ditemukan beberapa pengguna dengan nama serupa. Silakan gunakan daftar pencarian untuk memilih akun yang tepat.");
            }

            $target = $matches->first();
        } else {
            return back()->with('error', 'Masukkan nama teman yang ingin ditambahkan.');
        }

        // Check self addition
        if ($target->id === $user->id) {
            $msg = 'Anda tidak dapat menambahkan diri sendiri sebagai rekan.';
            return ($request->wantsJson() || $request->ajax())
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        // Check current status
        $status = $user->getFriendshipStatusWith($target->id);

        if ($status === 'friends') {
            $msg = "Anda sudah berteman dengan {$target->name}.";
            return ($request->wantsJson() || $request->ajax())
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('info', $msg);
        }

        if ($status === 'pending_sent') {
            $msg = "Permintaan pertemanan kepada {$target->name} sudah dikirim dan menunggu persetujuan.";
            return ($request->wantsJson() || $request->ajax())
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('info', $msg);
        }

        // If target had already sent a pending request to current user, auto-accept it!
        if ($status === 'pending_received') {
            Friendship::where('user_id', $target->id)->where('friend_id', $user->id)
                ->update(['status' => 'accepted']);

            Notification::create([
                'user_id' => $target->id,
                'type' => 'success',
                'title' => 'Permintaan Pertemanan Diterima',
                'description' => "{$user->name} telah menerima permintaan pertemanan Anda.",
                'is_read' => false,
            ]);

            $msg = "Permintaan pertemanan disetujui! Anda sekarang berteman dengan {$target->name}.";
            return ($request->wantsJson() || $request->ajax())
                ? response()->json(['success' => true, 'message' => $msg, 'status' => 'friends'])
                : back()->with('success', $msg);
        }

        // Status is 'none' -> Create new pending request
        DB::transaction(function () use ($user, $target) {
            Friendship::create([
                'user_id' => $user->id,
                'friend_id' => $target->id,
                'status' => 'pending',
            ]);

            Notification::create([
                'user_id' => $target->id,
                'type' => 'info',
                'title' => 'Permintaan Rekan Tim Baru',
                'description' => "{$user->name} mengirimkan permintaan pertemanan kepada Anda.",
                'is_read' => false,
            ]);
        });

        $msg = "Permintaan pertemanan berhasil dikirim ke {$target->name}. Menunggu persetujuan.";
        return ($request->wantsJson() || $request->ajax())
            ? response()->json(['success' => true, 'message' => $msg, 'status' => 'pending_sent'])
            : back()->with('success', $msg);
    }

    /**
     * Accept incoming friend request.
     */
    public function acceptFriend(Request $request, Friendship $friendship)
    {
        $user = Auth::user();

        if ($friendship->friend_id !== $user->id) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk menerima permintaan ini.');
        }

        $friendship->update(['status' => 'accepted']);

        Notification::create([
            'user_id' => $friendship->user_id,
            'type' => 'success',
            'title' => 'Permintaan Pertemanan Diterima',
            'description' => "{$user->name} telah menerima permintaan pertemanan Anda.",
            'is_read' => false,
        ]);

        $msg = "Permintaan pertemanan dari {$friendship->user->name} berhasil diterima!";
        return ($request->wantsJson() || $request->ajax())
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }

    /**
     * Remove or reject friend connection.
     */
    public function removeFriend(Request $request, Friendship $friendship)
    {
        $user = Auth::user();

        if ($friendship->user_id !== $user->id && $friendship->friend_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        $friendship->delete();

        $msg = "Koneksi pertemanan telah diperbarui.";
        return ($request->wantsJson() || $request->ajax())
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }

    /**
     * View Public User Profile.
     * Strictly protects sensitive data (no password, token, or private data).
     */
    public function showProfile(Request $request, User $user)
    {
        $currentUser = Auth::user();

        // Calculate public stats
        $totalWorkspaces = $user->groups()->count();
        $friendshipStatus = $currentUser ? $currentUser->getFriendshipStatusWith($user->id) : 'none';

        $profileData = [
            'id' => $user->id,
            'name' => $user->name,
            'role_title' => $user->role_title ?? 'Anggota Tim',
            'avatar_url' => $user->avatar_url,
            'joined_since' => $user->created_at ? $user->created_at->translatedFormat('d F Y') : 'Baru Bergabung',
            'total_workspaces' => $totalWorkspaces,
            'friendship_status' => $friendshipStatus,
            'is_self' => $currentUser && $currentUser->id === $user->id,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'user' => $profileData,
            ]);
        }

        return view('users.profile', compact('profileData'));
    }

    /**
     * Mark all notifications as read.
     */
    public function markNotificationsRead()
    {
        Auth::user()->unreadNotifications()->update(['is_read' => true]);
        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
