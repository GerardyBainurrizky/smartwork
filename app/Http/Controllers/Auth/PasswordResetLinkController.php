<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
        ]);

        $user = \App\Models\User::where('username', $request->username)->first();

        // Pesan yang ditampilkan dibuat sama untuk akun terdaftar maupun tidak,
        // agar keberadaan akun tidak dapat diketahui dari respon.
        if (! $user || ! $user->email) {
            return back()->with('status', __('passwords.sent'));
        }

        try {
            $status = Password::sendResetLink(
                ['email' => $user->email]
            );
        } catch (\Throwable $exception) {
            Log::error('Gagal mengirim email reset password.', [
                'username' => $request->username,
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('status', __('passwords.send_failed'));
        }

        return $status == Password::RESET_LINK_SENT
            ? back()->with('status', __('passwords.sent'))
            : back()->with('status', __('passwords.send_failed'));
    }
}