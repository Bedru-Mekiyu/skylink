<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Admin > My Profile. The logged-in admin edits only their own account (the user always comes from the session,
 * never from the URL). Changing the email or the password needs the current password.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.profile.edit', ['user' => $request->user()]);
    }

    /** Name and email. */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]); // Name@Example.com and name@example.com are the same address

        $data = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        if (strtolower($data['email']) !== strtolower((string) $user->email)) {
            $request->validateWithBag('profile', [
                'current_password' => ['required', 'current_password'],
            ], [
                'current_password.required' => 'Enter your current password to change your email address.',
                'current_password.current_password' => 'The current password is not correct.',
            ]);
        }

        $user->forceFill(['name' => $data['name'], 'email' => $data['email']])->save();

        return redirect()->route('admin.profile.edit')->with('success', 'Profile updated.');
    }

    /** New password: needs the current one; signs the account out everywhere else, keeps this session. */
    public function password(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'The current password is not correct.',
            'password.different' => 'The new password must be different from the current one.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $data['password']])->save(); // hashed by the model cast
        Auth::logoutOtherDevices($data['password']);                  // other sessions/devices are signed out, this one stays

        return redirect()->route('admin.profile.edit')->with('success', 'Password changed. Any other device that was signed in has been signed out.');
    }
}
