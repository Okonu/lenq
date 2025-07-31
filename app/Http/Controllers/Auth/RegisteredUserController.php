<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FirmMember;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        $invitationToken = session('invitation_token');

        return Inertia::render('Auth/Register', [
            'hasInvitation' => !empty($invitationToken),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $invitationToken = session('invitation_token');

        if ($invitationToken) {
            session()->forget('invitation_token');

            $member = FirmMember::where('invitation_token', $invitationToken)
                ->where('status', FirmMember::STATUS_INVITED)
                ->first();

            if ($member) {
                $member->update([
                    'user_id' => $user->id,
                    'status' => FirmMember::STATUS_ACTIVE,
                    'invitation_token' => null,
                    'invitation_accepted_at' => now(),
                ]);

                return redirect()->route('lawfirm.dashboard');
            }
        }

        return redirect(route('dashboard', absolute: false));
    }
}
