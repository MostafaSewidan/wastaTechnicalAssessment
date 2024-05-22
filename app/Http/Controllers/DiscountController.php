<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Discount;
use App\Models\DiscountPlan;
use App\Models\Product;
use App\Models\DiscountPlanDiscount;
use Spatie\Permission\Models\Role;
use Auth;

class DiscountController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('discount_plan')) {
            $lims_discount_all = Discount::with('discountPlans')->orderBy('id', 'desc')->get();
            return view('backend.discount.index', compact('lims_discount_all'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function create()
    {
        $lims_discount_plan_list = DiscountPlan::where('is_active', true)->get();
        return view('backend.discount.create', compact('lims_discount_plan_list'));
    }

    public function productSearch($code)
    {
        $lims_product_data = Product::where([
            ['code', $code],
            ['is_active', true]
        ])->select('id', 'name', 'code')->first();

        $product[] = $lims_product_data->id;
        $product[] = $lims_product_data->name;
        $product[] = $lims_product_data->code;
        return $product;
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['valid_from'] = date('Y-m-d', strtotime($data['valid_from']));
        $data['valid_till'] = date('Y-m-d', strtotime($data['valid_till']));
        if(isset($data['product_list'])) {
            $data['product_list'] = implode(",", $data['product_list']);
        }
        $data['days'] = implode(",", $data['days']);
        $lims_discount_data = Discount::create($data);
        foreach ($data['discount_plan_id'] as $key => $discount_plan_id) {
            DiscountPlanDiscount::create([
                'discount_id' => $lims_discount_data->id,
                'discount_plan_id' => $discount_plan_id
            ]);
        }
        return redirect()->route('discounts.index')->with('message', 'Discount created successfully');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $lims_discount_data = Discount::find($id);
        $discount_plan_ids = DiscountPlanDiscount::where('discount_id', $id)->pluck('discount_plan_id')->toArray();
        $lims_discount_plan_list = DiscountPlan::where('is_active', true)->get();
        return view('backend.discount.edit', compact('lims_discount_data', 'discount_plan_ids', 'lims_discount_plan_list'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $lims_discount_data = Discount::find($id);
        $data['valid_from'] = date('Y-m-d', strtotime(str_replace("/", "-", $data['valid_from'])));
        $data['valid_till'] = date('Y-m-d', strtotime(str_replace("/", "-", $data['valid_till'])));
        if(!isset($data['is_active']))
            $data['is_active'] = 0;
        if($data['applicable_for'] == 'All')
            $data['product_list'] = '';
        elseif(isset($data['product_list']))
            $data['product_list'] = implode(",", $data['product_list']);
        $data['days'] = implode(",", $data['days']);
        $pre_discount_plan_ids = DiscountPlanDiscount::where('discount_id', $id)->pluck('discount_plan_id')->toArray();
        //deleting previous discount plan id if not exist
        foreach ($pre_discount_plan_ids as $key => $discount_plan_id) {
            if(!in_array($discount_plan_id, $data['discount_plan_id'])) {
                DiscountPlanDiscount::where([
                    ['discount_plan_id', $discount_plan_id],
                    ['discount_id', $id]
                ])->first()->delete();
            }
        }
        //inserting new discount plan id
        foreach ($data['discount_plan_id'] as $key => $discount_plan_id) {
            if(!in_array($discount_plan_id, $pre_discount_plan_ids)) {
                DiscountPlanDiscount::create(['discount_plan_id' => $id, 'discount_id' => $id]);
            }
        }
        $lims_discount_data->update($data);
        return redirect()->route('discounts.index')->with('message', 'Discount updated successfully');
    }

    public function destroy($id)
    {
        //
    }
}
