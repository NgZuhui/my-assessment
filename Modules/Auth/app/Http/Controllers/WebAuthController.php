<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Auth\Http\Requests\LoginRequest;

/**
 * Session based login for the admin dashboard.
 */
class WebAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth::login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        // Only administrators may sign in to the dashboard.
        $credentials = $request->validated() + ['is_admin' => true];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match an administrator account.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.products.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
