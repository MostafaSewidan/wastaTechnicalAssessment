<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use DB;
use Auth;
use Cache;
use Illuminate\Support\Facades\URL;

class Common
{
    public function handle(Request $request, Closure $next)
    {
        /*if( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
            URL::forceScheme('https');
        }*/
        //get general setting value
        $general_setting =  Cache::remember('general_setting', 60*60*24*365, function () {
            return DB::table('general_settings')->latest()->first();
        });

        $todayDate = date("Y-m-d");
        if($general_setting->expiry_date) {
            $expiry_date = date("Y-m-d", strtotime($general_setting->expiry_date));
            if($todayDate > $expiry_date) {
                auth()->logout();
                return redirect()->route('contactForRenewal');
            }
        }
        //setting language
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
        $currency = Cache::remember('currency', 60*60*24*365, function () {
            $settingData = DB::table('general_settings')->select('currency')->latest()->first();
            return \App\Models\Currency::find($settingData->currency);
        });

        View::share('general_setting', $general_setting);
        View::share('currency', $currency);
        config(['staff_access' => $general_setting->staff_access, 'date_format' => $general_setting->date_format, 'currency' => $currency->code, 'currency_position' => $general_setting->currency_position, 'decimal' => $general_setting->decimal, 'is_zatca' => $general_setting->is_zatca, 'company_name' => $general_setting->company_name, 'vat_registration_number' => $general_setting->vat_registration_number, 'without_stock' => $general_setting->without_stock]);

        $alert_product = DB::table('products')->where('is_active', true)->whereColumn('alert_quantity', '>', 'qty')->count();
        $dso_alert_product = DB::table('dso_alerts')->select('number_of_products')->whereDate('created_at', date("Y-m-d"))->first();
        if($dso_alert_product)
            $dso_alert_product_no = $dso_alert_product->number_of_products;
        else
            $dso_alert_product_no = 0;
        View::share(['alert_product' => $alert_product, 'dso_alert_product_no' => $dso_alert_product_no]);
        $role = Cache::remember('user_role', 60*60*24*365, function () {
            return DB::table('roles')->find(Auth::user()->role_id);
        });
        View::share('role', $role);
        $permission_list = Cache::remember('permissions', 60*60*24*365, function () {
            return DB::table('permissions')->get();
        });
        View::share('permission_list', $permission_list);
        $role_has_permissions = Cache::remember('role_has_permissions', 60*60*24*365, function () {
            return DB::table('role_has_permissions')->where('role_id', Auth::user()->role_id)->get();
        });
        View::share('role_has_permissions', $role_has_permissions);

        $role_has_permissions_list = Cache::remember('role_has_permissions_list'.Auth::user()->role_id, 60*60*24*365, function () {
            return DB::table('permissions')->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')->where('role_id', Auth::user()->role_id)->select('permissions.name')->get();
        });
        View::share('role_has_permissions_list', $role_has_permissions_list);

        $categories_list = Cache::remember('category_list', 60*60*24*365, function () {
            return DB::table('categories')->where('is_active', true)->get();
        });
        View::share('categories_list', $categories_list);
        return $next($request);
    }
}
