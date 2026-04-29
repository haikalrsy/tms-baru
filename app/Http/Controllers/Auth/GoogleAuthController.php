<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    private array $adminEmails = [
        'admintms01@gmail.com',
        'admintms02@gmail.com',
    ];

    // GET /api/auth/google
    public function redirect()
    {
        $url = Socialite::driver('google')->stateless()->with(['prompt' => 'select_account'])->redirect()->getTargetUrl();
        return response()->json(['success' => true, 'url' => $url]);
    }

    // GET /auth/google/callback (web route)
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect(config('app.frontend_url') . '/login?error=google_failed');
        }

        $isAdminEmail = in_array($googleUser->getEmail(), $this->adminEmails);

        // Pakai withTrashed() supaya user yang soft-deleted (suspended) tetap ketemu
        $user = User::withTrashed()
                    ->where('google_id', $googleUser->getId())
                    ->orWhere('email', $googleUser->getEmail())
                    ->first();

        // ── Case Admin: Email terdaftar sebagai admin
        if ($isAdminEmail) {
            if (!$user) {
                $user = User::create([
                    'name'              => $googleUser->getName(),
                    'email'             => $googleUser->getEmail(),
                    'google_id'         => $googleUser->getId(),
                    'avatar'            => $googleUser->getAvatar(),
                    'password'          => bcrypt(Str::random(32)),
                    'role'              => 'admin',
                    'account_status'    => 'approved',
                    'email_verified_at' => now(),
                ]);
                ActivityLog::log('auth.admin_register', $user, [], [], $user->id);
            } else {
                $user->update([
                    'role'           => 'admin',
                    'account_status' => 'approved',
                    'google_id'      => $user->google_id ?? $googleUser->getId(),
                    'avatar'         => $googleUser->getAvatar(),
                ]);
            }

            $user->tokens()->where('name', 'api')->delete();
            $token = $user->createToken('api', ['*'], now()->addHours(12))->plainTextToken;

            $user->update(['last_login_at' => now(), 'is_online' => true]);
            ActivityLog::log('auth.google_login', $user, [], [], $user->id);

            return redirect(config('app.frontend_url') . '/auth/callback?token=' . $token);
        }

        // ── Case Suspended: Soft-deleted → restore, set pending, tampilkan pesan
        if ($user && $user->trashed()) {
            $user->restore();
            $user->update([
                'account_status' => 'pending',
                'google_id'      => $user->google_id ?? $googleUser->getId(),
                'avatar'         => $googleUser->getAvatar(),
            ]);
            ActivityLog::log('auth.google_reactivation_requested', $user, [], [], $user->id);

            return redirect(
                config('app.frontend_url') .
                '/auth/google/complete?status=suspended_reactivation' .
                '&need_phone=0'
            );
        }

        // ── Case 1: Driver, sudah ada & approved → langsung login
        if ($user && $user->account_status === 'approved') {
            if (!$user->google_id) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                ]);
            }

            $user->tokens()->where('name', 'api')->delete();

            $abilities = $user->isAdmin()
                ? ['*']
                : ['driver:read', 'driver:update', 'tracking:write', 'pod:create'];

            $token = $user->createToken('api', $abilities, now()->addHours(12))->plainTextToken;

            $user->update(['last_login_at' => now(), 'is_online' => true]);
            ActivityLog::log('auth.google_login', $user, [], [], $user->id);

            return redirect(config('app.frontend_url') . '/auth/callback?token=' . $token);
        }

        // ── Case 2: Sudah ada tapi pending/rejected
        if ($user) {
            $tempToken = $user->createToken('temp', ['temp:google'])->plainTextToken;
            $needPhone = is_null($user->phone) ? '1' : '0';

            return redirect(
                config('app.frontend_url') .
                '/auth/google/complete?token=' . $tempToken .
                '&status=' . $user->account_status .
                '&need_phone=' . $needPhone
            );
        }

        // ── Case 3: User baru (driver) → buat akun, minta no HP
        $newUser = User::create([
            'name'              => $googleUser->getName(),
            'email'             => $googleUser->getEmail(),
            'google_id'         => $googleUser->getId(),
            'avatar'            => $googleUser->getAvatar(),
            'password'          => bcrypt(Str::random(32)),
            'role'              => 'driver',
            'account_status'    => 'pending',
            'email_verified_at' => now(),
        ]);

        ActivityLog::log('auth.google_register', $newUser, [], [], $newUser->id);

        $tempToken = $newUser->createToken('temp', ['temp:google'])->plainTextToken;

        return redirect(
            config('app.frontend_url') . '/auth/google/complete?token=' . $tempToken
        );
    }

    // POST /api/auth/google/complete
    public function completeRegistration(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        if (!$request->user()->tokenCan('temp:google')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $user = $request->user();

        if ($user->account_status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Akun kamu sebelumnya ditolak oleh admin.',
                'code'    => 'ACCOUNT_REJECTED',
            ], 403);
        }

        $user->update(['phone' => $request->phone]);
        $user->currentAccessToken()->delete();
        ActivityLog::log('auth.google_complete', $user, [], ['phone' => $request->phone], $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil! Akun kamu sedang menunggu persetujuan admin.',
        ]);
    }
}