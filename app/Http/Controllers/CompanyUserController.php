<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CompanyUserController extends Controller
{
    /**
     * Authorize that the current authenticated user is Company Owner.
     */
    protected function authorizeOwner(): User
    {
        $user = Auth::user();
        if (!$user || !$user->isOwner()) {
            abort(403, 'Akses ditolak: Hanya Owner yang dapat mengakses manajemen pengguna.');
        }

        return $user;
    }

    /**
     * Display a listing of company users.
     */
    public function index(Request $request)
    {
        $this->authorizeOwner();

        $query = User::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = strtolower(trim($request->input('role')));
            if (in_array($role, ['owner', 'admin'], true)) {
                $query->where(function ($q) use ($role) {
                    $q->where('role', $role)->orWhere('role_title', $role);
                });
            }
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $stats = [
            'total' => User::count(),
            'owners' => User::where('role', 'owner')->orWhere('role_title', 'Owner')->count(),
            'admins' => User::where('role', 'admin')->orWhere('role_title', 'Admin')->count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('company.users.index', compact('users', 'stats'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $currentUser = $this->authorizeOwner();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,filter', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:owner,admin'],
            'password' => ['required', 'string', 'min:8'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'role.required' => 'Peran akun wajib dipilih.',
            'role.in' => 'Peran harus berupa Owner atau Admin.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
        ]);

        $role = strtolower($validated['role']);
        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $role,
            'role_title' => ucfirst($role),
            'password' => Hash::make($validated['password']),
            'whatsapp' => $request->filled('whatsapp') ? trim($request->input('whatsapp')) : null,
            'is_active' => true,
        ]);

        ActivityLogger::log('user_created', "Pengguna baru '{$user->name}' ({$user->email}) dibuat dengan peran {$user->role_title}.", $user, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => true,
        ], $currentUser);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Pengguna {$user->name} berhasil dibuat.",
                'data' => $user,
            ], 201);
        }

        return redirect()->route('company.users.index')->with('success', "Pengguna {$user->name} berhasil ditambahkan sebagai {$user->role_title}.");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $currentUser = $this->authorizeOwner();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,filter', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'whatsapp' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
        ]);

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => $user->whatsapp,
        ];

        $user->update([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'whatsapp' => $request->filled('whatsapp') ? trim($request->input('whatsapp')) : null,
        ]);

        ActivityLogger::log('user_updated', "Informasi pengguna '{$user->name}' diperbarui.", $user, [
            'before' => $before,
            'after' => [
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
            ],
        ], $currentUser);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Pengguna {$user->name} berhasil diperbarui.",
                'data' => $user,
            ]);
        }

        return redirect()->route('company.users.index')->with('success', "Informasi pengguna {$user->name} berhasil diperbarui.");
    }

    /**
     * Change user role (Owner or Admin).
     */
    public function changeRole(Request $request, User $user)
    {
        $currentUser = $this->authorizeOwner();

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:owner,admin'],
        ], [
            'role.required' => 'Peran baru wajib dipilih.',
            'role.in' => 'Peran harus Owner atau Admin.',
        ]);

        $newRole = strtolower($validated['role']);
        $oldRole = strtolower($user->role ?? $user->role_title ?? 'admin');

        if ($oldRole === $newRole) {
            return back()->with('info', "Pengguna {$user->name} sudah memiliki peran {$user->role_title}.");
        }

        // Prevent self-role escalation/demotion if user is changing their own role
        if ($currentUser->id === $user->id && $newRole !== 'owner') {
            abort(422, 'Anda tidak dapat menurunkan peran akun Anda sendiri.');
        }

        // Last Active Owner Protection: Cannot demote the last remaining active Owner
        if ($oldRole === 'owner' && $newRole === 'admin' && $user->isLastActiveOwner()) {
            abort(422, 'Tidak dapat menurunkan peran Owner terakhir. Perusahaan wajib memiliki minimal satu Owner aktif.');
        }

        $user->update([
            'role' => $newRole,
            'role_title' => ucfirst($newRole),
        ]);

        // Keep group_members table synchronized with authoritative user role
        \App\Models\GroupMember::where('user_id', $user->id)->update([
            'role' => $newRole === 'owner' ? 'owner' : 'admin',
        ]);

        ActivityLogger::log('role_changed', "Peran pengguna '{$user->name}' diubah dari " . ucfirst($oldRole) . " menjadi " . ucfirst($newRole) . ".", $user, [
            'old_role' => $oldRole,
            'new_role' => $newRole,
        ], $currentUser);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Peran {$user->name} berhasil diubah menjadi {$user->role_title}.",
                'data' => $user,
            ]);
        }

        return redirect()->route('company.users.index')->with('success', "Peran {$user->name} berhasil diubah menjadi {$user->role_title}.");
    }

    /**
     * Activate or deactivate a user account.
     */
    public function toggleStatus(Request $request, User $user)
    {
        $currentUser = $this->authorizeOwner();

        if ($currentUser->id === $user->id) {
            abort(422, 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $newStatus = !$user->isActive();

        // Last Active Owner Protection: Cannot deactivate the last remaining active Owner
        if (!$newStatus && $user->isLastActiveOwner()) {
            abort(422, 'Tidak dapat menonaktifkan Owner terakhir. Perusahaan wajib memiliki minimal satu Owner aktif.');
        }

        $user->update(['is_active' => $newStatus]);

        $action = $newStatus ? 'user_activated' : 'user_deactivated';
        $desc = $newStatus
            ? "Akun pengguna '{$user->name}' diaktifkan kembali."
            : "Akun pengguna '{$user->name}' dinonaktifkan.";

        ActivityLogger::log($action, $desc, $user, [
            'is_active' => $newStatus,
        ], $currentUser);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $desc,
                'data' => [
                    'id' => $user->id,
                    'is_active' => $newStatus,
                ],
            ]);
        }

        return redirect()->route('company.users.index')->with('success', $desc);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $currentUser = $this->authorizeOwner();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLogger::log('password_reset', "Password untuk pengguna '{$user->name}' telah direset oleh Owner.", $user, [
            'user_id' => $user->id,
            'user_name' => $user->name,
        ], $currentUser);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Password pengguna {$user->name} berhasil direset.",
            ]);
        }

        return redirect()->route('company.users.index')->with('success', "Password untuk {$user->name} berhasil diperbarui.");
    }
}

