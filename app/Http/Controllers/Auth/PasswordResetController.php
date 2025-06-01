<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ViewErrorBag;

class PasswordResetController extends Controller
{
    public function showResetForm(Request $request, $token)
    {
        return view('auth.reset-with-old', [
            'token' => $token,
            'email' => $request->query('email'),
            'errors' => session()->get('errors', new ViewErrorBag), // Pastikan $errors tersedia
        ]);
    }
    /**
     * Handle forgot password: send reset link to email
     */
    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        Log::info('Request to send reset link: ' . $request->email);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        Log::info('Reset link send status: ' . $status);

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent to your email.'], 200)
            : response()->json(['message' => 'Unable to send reset link.'], 500);
    }

    /**
     * Handle reset password using token
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|confirmed|min:8',
        ]);

        Log::info('Attempting password reset for: ' . $request->email);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                Log::info('Password reset successful for user: ' . $user->email);
            }
        );

        Log::info('Final reset status: ' . $status);

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password has been reset.'], 200)
            : response()->json(['message' => 'Failed to reset password.'], 400);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 403);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password successfully changed.'], 200);
    }

}
