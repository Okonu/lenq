<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\FirmMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $invitationToken = session('invitation_token');

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'hasInvitation' => !empty($invitationToken),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $invitationToken = session('invitation_token');

        if ($invitationToken) {
            session()->forget('invitation_token');

            $member = FirmMember::where('invitation_token', $invitationToken)
                ->where('status', FirmMember::STATUS_INVITED)
                ->first();

            if ($member) {
                $member->update([
                    'user_id' => Auth::id(),
                    'status' => FirmMember::STATUS_ACTIVE,
                    'invitation_token' => null,
                    'invitation_accepted_at' => now(),
                ]);

                return redirect()->route('lawfirm.dashboard');
            }
        }

        $firmMember = FirmMember::where('user_id', Auth::id())
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        if ($firmMember) {
            return redirect()->route('lawfirm.dashboard');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
