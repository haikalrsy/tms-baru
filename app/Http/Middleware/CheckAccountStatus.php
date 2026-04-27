<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAccountStatus
{
    private array $messages = [
        'pending'   => 'Your account is pending approval. Please wait for admin confirmation.',
        'rejected'  => 'Your account registration was rejected. Contact admin for more info.',
        'suspended' => 'Your account has been suspended. Contact admin.',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) return $next($request);

        // ── Bypass untuk Google OAuth temp token ──────────────────────────────
        if ($user->tokenCan('temp:google')) {
            return $next($request);
        }

        if ($user->account_status !== 'approved') {
            return response()->json([
                'success'        => false,
                'message'        => $this->messages[$user->account_status] ?? 'Account not active.',
                'account_status' => $user->account_status,
                'code'           => 'ACCOUNT_' . strtoupper($user->account_status),
            ], 403);
        }

        return $next($request);
    }
}