<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.form', ['register' => false]);
    }

    public function register()
    {
        return view('auth.form', ['register' => true]);
    }

    public function store(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users', 'password' => 'required|string|min:8|confirmed']);
        $user = User::create($data);
        Auth::login($user);
        $r->session()->regenerate();

        return redirect()->route('home')->with('success', 'Tạo tài khoản thành công.');
    }

    public function authenticate(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($data)) {
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không đúng.']);
        }
        $r->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('home');
    }
}
