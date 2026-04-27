<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AccountController extends Controller
{
    // GET /api/admin/accounts
    public function index(Request $request)
    {
        $accounts = User::with('driver')
            ->when($request->status, fn($q, $v) => $q->where('account_status', $v))
            ->when($request->role,   fn($q, $v) => $q->where('role', $v))
            ->when($request->search, fn($q, $v) =>
                $q->where('name', 'like', "%$v%")->orWhere('email', 'like', "%$v%")
            )
            ->orderByRaw("FIELD(account_status, 'pending', 'approved', 'suspended', 'rejected')")
            ->paginate($request->per_page ?? 20);

        return response()->json(['success' => true, 'data' => $accounts]);
    }

    // POST /api/admin/accounts/{user}/approve
    public function approve(Request $request, User $user)
    {
        if ($user->account_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Account is not in pending status.'], 422);
        }

        $user->update([
            'account_status' => 'approved',
            'approved_by'    => $request->user()->id,
            'approved_at'    => now(),
        ]);

        Mail::to($user->email)->send(new AccountApprovedMail($user));
        ActivityLog::log('account.approved', $user);

        return response()->json(['success' => true, 'message' => 'Account approved.', 'data' => $user]);
    }

    // POST /api/admin/accounts/{user}/reject
    public function reject(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user->update(['account_status' => 'rejected']);

        Mail::to($user->email)->send(new AccountRejectedMail($user, $request->reason));
        ActivityLog::log('account.rejected', $user);

        return response()->json(['success' => true, 'message' => 'Account rejected.']);
    }

    // POST /api/admin/accounts/{user}/suspend
    public function suspend(Request $request, User $user)
    {
        // Admin tidak bisa di-suspend
        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Admin account cannot be suspended.',
            ], 403);
        }

        $user->update(['account_status' => 'suspended']);
        $user->tokens()->delete();
        ActivityLog::log('account.suspended', $user);

        return response()->json(['success' => true, 'message' => 'Account suspended.']);
    }

    // POST /api/admin/accounts
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:admin,driver',
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => bcrypt($validated['password']),
            'role'              => $validated['role'],
            'account_status'    => 'approved',
            'approved_by'       => $request->user()->id,
            'approved_at'       => now(),
            'email_verified_at' => now(),
        ]);

        ActivityLog::log('account.created', $user);
        return response()->json(['success' => true, 'data' => $user], 201);
    }
}