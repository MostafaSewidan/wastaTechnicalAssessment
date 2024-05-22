<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiscountPlan;
use App\Models\DiscountPlanWarehouse;  // تم تغيير الاسم هنا
use App\Models\Warehouse;  // تم تغيير الاسم هنا
use Spatie\Permission\Models\Role;
use Auth;

class DiscountPlanController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('discount_plan')) {
            $lims_discount_plan_all = DiscountPlan::with('warehouses')->orderBy('id', 'desc')->get();  // تم تغيير الاسم هنا
            return view('backend.discount_plan.index', compact('lims_discount_plan_all'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    // ... الكواد الباقية

    public function create()
    {
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();  // تم تغيير الاسم هنا
        return view('backend.discount_plan.create', compact('lims_warehouse_list'));  // تم تغيير الاس هنا
    }

    // ... الكواد الباقية

    public function store(Request $request)
    {
        $data = $request->all();
        if(!isset($data['is_active'])) {
            $data['is_active'] = 0;
        }
        $lims_discount_plan = DiscountPlan::create($data);
        foreach ($data['warehouse_id'] as $key => $warehouse_id) {  // تم تغيير الاسم هنا
            DiscountPlanWarehouse::create(['discount_plan_id' => $lims_discount_plan->id, 'warehouse_id' => $warehouse_id]);  // تم تغيير الام هنا
        }
        return redirect()->route('discount-plans.index')->with('message', 'DiscountPlan created successfully');
    }

    // ... الأكاد الباقية

    public function edit($id)
    {
        $lims_discount_plan = DiscountPlan::find($id);
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();  // تم تغيير الاسم هن
        $warehouse_ids = DiscountPlanWarehouse::where('discount_plan_id', $id)->pluck('warehouse_id')->toArray();  // تم تيير الاسم هنا
        return view('backend.discount_plan.edit', compact('lims_discount_plan', 'lims_warehouse_list', 'warehouse_ids'));  // تم تيير الاسم هنا
    }

    // ... الأكواد الاقية
}
