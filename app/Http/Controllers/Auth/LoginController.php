<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Reject deactivated accounts. Runs only after the password matched, so it never reveals
     * whether an email is registered.
     */
    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
        if ($user->is_active === false) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw \Illuminate\Validation\ValidationException::withMessages([
                $this->username() => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
            ]);
        }
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(\Illuminate\Http\Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
