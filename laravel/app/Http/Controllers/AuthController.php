<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ((bool) $request->session()->get('logged_in', false)) {
            return redirect()->route('admin.index');
        }

        return view('login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:256'],
        ]);
        $expectedUser = (string) config('services.namdel.admin_user', '');
        $expectedPassword = (string) config('services.namdel.admin_pass', '');

        if ($expectedUser === '' || $expectedPassword === ''
            || ! hash_equals($expectedUser, $credentials['username'])
            || ! hash_equals($expectedPassword, $credentials['password'])) {
            return back()->with('error', 'Username atau Password salah!')->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put([
            'logged_in' => true,
            'admin_user' => $credentials['username'],
        ]);
        DB::table('activity_logs')->insert([
            'actor' => $credentials['username'],
            'action' => 'login',
            'target' => 'admin',
            'created_at' => now()->toDateTimeString(),
        ]);

        return redirect()->route('admin.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kamu berhasil keluar.');
    }
}
