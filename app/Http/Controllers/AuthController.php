<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Notification;

class AuthController extends Controller
{
    /**
     * Show modern split-screen register view.
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    /**
     * Handle user registration with secure server-side validation.
     * All public registrations automatically receive the Admin role.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,filter', 'max:255', 'unique:users,email'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar dalam sistem.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter untuk keamanan akun.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $cleanWhatsapp = null;
        if ($request->filled('whatsapp')) {
            $cleanWhatsapp = trim($request->input('whatsapp'));
        }

        // Security: Always enforce 'admin' role on new public registrations.
        // Ignore any manually injected 'role' or 'role_title' parameter.
        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'whatsapp' => $cleanWhatsapp,
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'role_title' => 'Admin',
            'is_active' => true,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'success',
            'title' => 'Selamat Datang di Dadhen Mandiri',
            'description' => 'Akun kolaborasi spreadsheet Anda berhasil dibuat.',
            'is_read' => false,
        ]);

        \App\Services\ActivityLogger::log('register', "Pengguna baru {$user->name} mendaftar sebagai Admin.", $user);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth_user_role', 'admin');

        return redirect()->route('dashboard')->with('success', 'Akun berhasil didaftarkan! Selamat datang di Dadhen Mandiri.');
    }

    /**
     * Show login view.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Handle authentication with rate limiting and secure session management.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $throttleKey = Str::transliterate(strtolower($request->input('email')) . '|' . $request->ip());

        // Check Rate Limiter (Max 5 attempts per minute)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Terlalu banyak percobaan masuk yang gagal. Silakan coba lagi dalam {$seconds} detik.",
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');
        $cleanEmail = strtolower(trim($credentials['email']));

        $candidateUser = User::where('email', $cleanEmail)->first();
        if ($candidateUser && !$candidateUser->isActive()) {
            return back()->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Owner.',
            ])->onlyInput('email');
        }

        if (Auth::attempt([
            'email' => $cleanEmail,
            'password' => $credentials['password'],
        ], $remember)) {
            $user = Auth::user();

            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user->update(['last_active_at' => now()]);

            \App\Services\ActivityLogger::log('login', "Pengguna {$user->name} berhasil masuk ke sistem.", $user, [
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang kembali, ' . $user->name);
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'Kredensial email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Logout and terminate session securely.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            \App\Services\ActivityLogger::log('logout', "Pengguna {$user->name} keluar dari sistem.", $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah keluar dari sistem.');
    }
}
