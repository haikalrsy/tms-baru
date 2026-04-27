<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailVerificationCode;
use App\Models\ActivityLog;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // ─── LOGIN ────────────────────────────────────────────────────────────────

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email belum diverifikasi.',
                'code'    => 'EMAIL_NOT_VERIFIED',
            ], 403);
        }

        if ($user->account_status !== 'approved') {
            $messages = [
                'pending'   => 'Akun sedang menunggu persetujuan admin.',
                'rejected'  => 'Akun kamu ditolak oleh admin.',
                'suspended' => 'Akun kamu disuspend.',
            ];
            return response()->json([
                'success'        => false,
                'message'        => $messages[$user->account_status] ?? 'Account not active.',
                'account_status' => $user->account_status,
                'code'           => 'ACCOUNT_' . strtoupper($user->account_status),
            ], 403);
        }

        $user->tokens()->where('name', 'api')->delete();

        $abilities = $user->isAdmin()
            ? ['*']
            : ['driver:read', 'driver:update', 'tracking:write', 'pod:create'];

        $token = $user->createToken('api', $abilities, now()->addHours(12));

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'is_online'     => true,
        ]);

        ActivityLog::log('auth.login', $user, [], [], $user->id);

        return response()->json([
            'success'    => true,
            'token'      => $token->plainTextToken,
            'expires_at' => now()->addHours(12)->toISOString(),
            'user'       => $this->formatUser($user),
        ]);
    }

    // ─── REGISTER ─────────────────────────────────────────────────────────────

    public function register(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'phone'                 => 'required|string|max:20',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'password'       => Hash::make($request->password),
            'role'           => 'driver',
            'account_status' => 'pending',
        ]);

        $this->sendVerificationCode($user);

        ActivityLog::log('auth.register', $user, [], [], $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil! Kode verifikasi dikirim ke email kamu.',
        ], 201);
    }

    // ─── VERIFY EMAIL ─────────────────────────────────────────────────────────

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email sudah terverifikasi.'], 400);
        }

        $record = EmailVerificationCode::where('user_id', $user->id)
            ->where('code', $request->code)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Kode tidak valid.'], 422);
        }

        if ($record->isExpired()) {
            return response()->json(['success' => false, 'message' => 'Kode sudah kadaluarsa.', 'code' => 'CODE_EXPIRED'], 422);
        }

        // Tandai kode sebagai used
        $record->update(['used_at' => now()]);
        $user->update(['email_verified_at' => now()]);

        ActivityLog::log('auth.email_verified', $user, [], [], $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Email berhasil diverifikasi! Akun kamu sedang menunggu persetujuan admin.',
        ]);
    }

    // ─── RESEND CODE ──────────────────────────────────────────────────────────

    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Email tidak ditemukan.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email sudah terverifikasi.'], 400);
        }

        // Cek rate limit: max 1 kode per 1 menit
        $lastCode = EmailVerificationCode::where('user_id', $user->id)
            ->latest()
            ->first();

        if ($lastCode && $lastCode->created_at->diffInSeconds(now()) < 60) {
            $wait = 60 - $lastCode->created_at->diffInSeconds(now());
            return response()->json([
                'success' => false,
                'message' => "Tunggu {$wait} detik sebelum kirim ulang.",
                'wait_seconds' => $wait,
            ], 429);
        }

        $this->sendVerificationCode($user);

        return response()->json(['success' => true, 'message' => 'Kode verifikasi dikirim ulang.']);
    }

    // ─── LOGOUT / ME / FCM ───────────────────────────────────────────────────

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $user->update(['is_online' => false]);
        ActivityLog::log('auth.logout', $user, [], [], $user->id);
        return response()->json(['success' => true, 'message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('driver');
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['success' => true, 'message' => 'FCM token updated.']);
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────────

    private function sendVerificationCode(User $user): void
    {
        // Invalidate kode lama
        EmailVerificationCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'user_id'    => $user->id,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new VerificationCodeMail($user, $code));
    }

    private function formatUser(User $user): array
    {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'phone'          => $user->phone,
            'avatar'         => $user->avatar,
            'role'           => $user->role,
            'account_status' => $user->account_status,
            'is_google'      => !is_null($user->google_id),
        ];
    }
}