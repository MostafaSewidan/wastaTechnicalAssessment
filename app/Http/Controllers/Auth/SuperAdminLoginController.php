<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Illuminate\Http\Request;
use App\User;

class SuperAdminLoginController extends Controller
{
    use AuthenticatesUsers;

    public function login()
    {
        if(isset($_COOKIE['language']))
            \App::setLocale($_COOKIE['language']);
        else
            \App::setLocale('en');
        //getting theme
        if(isset($_COOKIE['theme']))
            $theme = $_COOKIE['theme'];
        else
            $theme = 'light';
        $general_setting = \App\GeneralSetting::latest()->first();
        return view('landlord.login', compact('theme', 'general_setting'));
    }

    public function store(Request $request)
    {
        $credentials = $request->except(['_token']);
        if(auth()->attempt($credentials)) {
            return redirect()->route('superadmin.dashboard');
        }
        else {
            return redirect()->back()->with('not_permitted', 'Invalid username or password');
        }
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return redirect('/');
    }
}