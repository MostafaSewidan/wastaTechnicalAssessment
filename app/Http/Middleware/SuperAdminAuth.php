<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\View;
use DB;

class SuperAdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(Auth::check()) {
            if(isset($_COOKIE['language'])) {
                \App::setLocale($_COOKIE['language']);
            } 
            else {
                \App::setLocale('en');
            }
            //setting theme
            if(isset($_COOKIE['theme'])) {
                View::share('theme', $_COOKIE['theme']);
            }
            else {
                View::share('theme', 'light');
            }
            //get general setting value        
            $general_setting = DB::table('general_settings')->latest()->first();
            $default_language = DB::table('languages')->where('is_default', true)->first();
            if($default_language)
                $lang_id = $default_language->id;
            else
                $lang_id = 1;
            View::share('general_setting', $general_setting);
            View::share('lang_id', $lang_id);
            config(['date_format' => $general_setting->date_format, 'lang_id' => $lang_id]);
            return $next($request);
        }
        return redirect('superadmin-login');
    }
}
