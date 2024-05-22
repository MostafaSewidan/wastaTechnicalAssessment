<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Currency;
use App\Models\GeneralSetting;
use Auth;
use Cache;

class CurrencyController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('currency')) {
            $lims_currency_all = Currency::where('is_active', true)->get();
            return view('backend.currency.index', compact('lims_currency_all'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data = $request->all();
        Currency::create($data);
        cache()->forget('currency');
        return redirect()->back()->with('message', 'Currency created successfully');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        if($data['exchange_rate'] == 1) {
            GeneralSetting::latest()->first()->update(['currency' => $data['currency_id']]);
        }
        Currency::find($data['currency_id'])->update($data);
        cache()->forget('currency');
        return redirect()->back()->with('message', 'Currency updated successfully');
    }

    public function destroy($id)
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        Currency::find($id)->update(['is_active' => false]);
        cache()->forget('currency');
        return redirect()->back()->with('message', 'Currency deleted successfully');
    }
}
