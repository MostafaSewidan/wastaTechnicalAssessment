<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomSales;
use App\Models\CustomProducts;
use App\Models\CustomProductImgs;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redirect;
use App\Models\Customer;
use App\Models\User;
use App\Models\CustomerGroup;
use App\Models\Warehouse;
use App\Models\PosSetting;
use DB;
use Auth;
use Cache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;


class CustomSalesController extends Controller
{
    use \App\Traits\TenantInfo;
    use \App\Traits\MailInfo;

    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('sales-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';

            if($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            if($request->input('sale_status'))
                $sale_status = 3;
            else
                $sale_status = 0;

            if($request->input('payment_status'))
                $payment_status = $request->input('payment_status');
            else
                $payment_status = 0;

            if($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            }
            else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d') )))));
                $ending_date = date("Y-m-d");
            }
                        $lims_pos_setting_data = PosSetting::latest()->first();

 if($lims_pos_setting_data)
                $options = explode(',', $lims_pos_setting_data->payment_options);
            else
                $options = [];
        return view('backend.customsales.index', compact('starting_date', 'ending_date', 'warehouse_id', 'sale_status', 'payment_status', 'all_permission','options'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }
    public function getdata(Request $request)
    {
                $warehouse_id = $request->input('warehouse_id');
                $trash = $request->input('trash');
                $search = $request->input('search.value');
                $sale_status = $request->input('sale_status');

                if (!$trash) {
                    $query = CustomSales::join('customers','custom_sales.customer_id', '=','customers.id')
                    ->join('users','users.id', '=','custom_sales.user_id')
                    ->leftjoin('custom_products','custom_sales.id', '=','custom_products.sale_id')
                    ->where('custom_sales.is_active',true)
                    ->whereDate('custom_sales.created_at', '>=' ,$request->input('starting_date'))
                    ->whereDate('custom_sales.created_at', '<=' ,$request->input('ending_date'));
                }
                else
                {
                    $query = CustomSales::join('customers','custom_sales.customer_id', '=','customers.id')
                    ->join('users','users.id', '=','custom_sales.user_id')
                    ->leftjoin('custom_products','custom_sales.id', '=','custom_products.sale_id')
                    ->where('custom_sales.is_active',false)
                    ->whereDate('custom_sales.created_at', '>=' ,$request->input('starting_date'))
                    ->whereDate('custom_sales.created_at', '<=' ,$request->input('ending_date'));;

                }

                if (!empty($search)){
                    $query = $query->where('custom_sales.reference_no', 'LIKE', "%{$search}%")
                                   ->orwhere('customers.name', 'LIKE', "%{$search}%")
                                   ->orwhere('customers.phone_number', 'LIKE', "%{$search}%")
                                   ->orwhere('customers.phone_number2', 'LIKE', "%{$search}%")
                                   ->orwhere('custom_products.name', 'LIKE', "%{$search}%")
                                   ->orwhere('users.name', 'LIKE', "%{$search}%")
                                   ->orwhere('custom_products.describtion', 'LIKE', "%{$search}%");
                }
                if($sale_status)
                    $query = $query->where('custom_sales.sale_status', $sale_status);
                if($warehouse_id)
                    $query = $query->where('custom_sales.warehouse_id', $warehouse_id);
                  
                  $totalData = $query->count();

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');

                  $query = $query->select('custom_sales.reference_no','custom_sales.id','custom_sales.sale_status','custom_sales.shipping_cost','custom_sales.order_discount_type','custom_sales.order_discount_value', 'customers.name','customers.phone_number','customers.phone_number2','customers.state','customers.city','customers.address','users.name as user_name','custom_sales.created_at','custom_sales.printed','custom_sales.shipping_with')
                        ->offset($start)
                        ->limit($limit)
                        ->orderby('created_at','DESC')
                        ->get();
                        if (!empty($search))
                        $totalFiltered = $query->count();
                        else
                        $totalFiltered = $totalData;
                  $data = array();
                        foreach ($query as $key => $sale) {
                            $nestedData['id'] = $sale->id;
                            $nestedData['key'] = $key;
                            $nestedData['date'] = date('Y-m-d h:i a', strtotime($sale->created_at));
                            $nestedData['reference_no'] = $sale->reference_no;
                            $nestedData['shipping_cost'] = $sale->shipping_cost;
                            $nestedData['customer_name'] = $sale->name;
                            $nestedData['phone_number'] = $sale->phone_number;
                            $nestedData['phone_number2'] = $sale->phone_number2;
                            $nestedData['state'] = $sale->state;
                            $nestedData['city'] = $sale->city;
                            $nestedData['address'] = $sale->address;
                            $nestedData['user_name'] = $sale->user_name;
                            $nestedData['products'] = '';
                            
                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                
                                <li>
                                    <button type="button" class="btn btn-link view"><i class="fa fa-eye"></i> '.trans('file.View').'</button>
                                </li>';
                $nestedData['options'] .=
                        '<li>
                            <button type="button" class="viewlog btn btn-link" data-id = "'.$sale->id.'"><i class="fa fa-list"></i> '.trans('file.View Log').'</button>
                        </li>';
                $nestedData['options'] .= '<li>
                            <a href="'.route('customsales.edit', $sale->id).'" class="btn btn-link"><i class="dripicons-document-edit"></i> '.trans('file.edit').'</a>
                            </li>';

                    $nestedData['options'] .= \Form::open(["route" => ["customsales.destroy", $sale->id], "method" => "DELETE"] ).'
                            <li>
                              <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.trans("file.delete").'</button>
                            </li>'.\Form::close().'
                        </ul>
                    </div>';
                if(!empty($sale->shipping_with))
                $nestedData['shipping_method'] = $sale->shipping_with;
                else
                $nestedData['shipping_method'] = 'None';

              if(!$sale->printed){
               $nestedData['printed'] = '<div class="badge badge-danger">'.trans('file.Not Printed').'</div>';
              }
              else
              {
               $nestedData['printed'] = '<div class="badge badge-success">'.trans('file.Printed').'</div>';
              }
                            $products = CustomProducts::where('sale_id',$sale->id)->get();

                            foreach ($products as $product) {

                                $nestedData['products'] .= $product->name.' ('.$product->qty.')';
                            }


                if($sale->sale_status == 1){
                    $nestedData['sale_status'] = '<div class="badge badge-success">'.trans('file.confirm').'</div>';
                    $sale_status = trans('file.confirm');
                }
                elseif($sale->sale_status == 2){
                    $nestedData['sale_status'] = '<div class="badge badge-primary">'.trans('file.Due').'</div>';
                    $sale_status = trans('file.Due');
                }
                elseif($sale->sale_status == 3){
                    $nestedData['sale_status'] = '<div class="badge badge-secondary">'.trans('file.Returned').'</div>';
                    $sale_status = trans('file.Returned');
                }
                elseif($sale->sale_status == 4){
                    $nestedData['sale_status'] = '<div class="badge badge-danger">'.trans('file.Ready To Backup').'</div>';
                    $sale_status = trans('file.Ready To Backup');
                }
                elseif($sale->sale_status == 5){ // ت تعدي ال هنا
                    $nestedData['sale_status'] = '<div class="badge badge-info">'.trans('file.Out of deliverey').'</div>';
                    $sale_status = trans('file.Out of deliverey');
                }
                elseif($sale->sale_status == 6){ // تم تد الق هنا
                    $nestedData['sale_status'] = '<div class="badge badge-info">'.trans('file.Partial collected').'</div>';
                    $sale_status = trans('file.Partial collected');
                }
                elseif($sale->sale_status == 10){ // تم تد الق هنا
                    $nestedData['sale_status'] = '<div class="badge badge-success" style="background-color: #31c1b3!important;">'.trans('file.Order Delivered').'</div>';
                    $sale_status = trans('file.sent delivered handed');
                }
                elseif($sale->sale_status == 7){ // تم ت القم هنا
                    $nestedData['sale_status'] = '<div class="badge badge-info">'.trans('file.Draft').'</div>';
                    $sale_status = trans('file.Draft');
                }
                elseif($sale->sale_status == 8){ // تم د ارق هنا
                    $nestedData['sale_status'] = '<div class="badge badge-info">'.trans('file.Delivered').'</div>';
                    $sale_status = trans('file.Delivered');
                }
                elseif($sale->sale_status == 9){ //  تد م هنا
                    $nestedData['sale_status'] = '<div class="badge badge-danger">'.trans('file.Partial Returned').'</div>';
                    $sale_status = trans('file.Partial Returned');
                }
                if ($sale->is_partcollected != 0) {
                    $nestedData['sale_status'] .= '<div class="badge badge-danger">'.trans('file.Has Uncollected').'</div>';
                }
                $nestedData['sale'] = $sale->id;
                                $data[] = $nestedData;

                        }

        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        echo json_encode($json_data);


    }
    public function create(){
        $lims_pos_setting_data = Cache::remember('pos_setting', 60*60*24*30, function () {
                return PosSetting::latest()->first();
            });

            $lims_customer_group_all = Cache::remember('customer_group_list', 60*60*24, function () {
                return CustomerGroup::where('is_active', true)->get();
            });
        return view('backend.customsales.create',compact('lims_pos_setting_data','lims_customer_group_all'));
    }

    public function store(Request $request){
        $data = $request->except('product_images');

        $reference_no = 'CS' . date("ymd") . '-'. rand(10000,99999).'-'.rand(10000,99999);
        $warehouse_id = 1;
        $customer_id = $data['customer_id'];
        $shipping_cost = $data['shipping_cost'];
        $items = 1;
        $user_id = Auth::id();

        $sale = CustomSales::create([
            'reference_no' => $reference_no,
            'warehouse_id' => $warehouse_id,
            'customer_id'  => $customer_id,
            'user_id' => $user_id,
            'items' => $items,
            'shipping_cost' => $shipping_cost
        ]);
            $name = $data['product_name'];
            $describtion = $data['product_desc'];
            $qty = $data['product_qty'];
            $images = $request->file('product_images');

            $product = CustomProducts::create([
                'name' => $name,
                'describtion' => $describtion,
                'qty' => $qty,
                'sale_id' => $sale->id,


            ]);
if($request->hasFile('product_images')) {
            foreach ($images as $image) {
               // return $image;
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis").rand(10000,99999) . 'custom';
                    $imageName = $imageName . '.' . $ext;

                   $sign = $image->move('public/images/product', $imageName);


                    if ($sign) {
                        CustomProductImgs::create([
                            'product_id' => $product->id,
                            'image' => $imageName,
                        ]);
                    }
            }
}
        $sale->total_qty = $qty;
        $sale->save();
                    return redirect()->back()->with('success', 'Sale created successfully');



    }
    public function getsale($id){
        $sale = CustomSales::find($id);

        $data = [];

        $data['reference_no'] = $sale->reference_no;
        $data['items'] = $sale->items;
        $data['shipping_cost'] = $sale->shipping_cost;
        $data['total_qty'] = $sale->total_qty;
        $data['customer'] = Customer::find($sale->customer_id)->name;
        $data['phone'] = Customer::find($sale->customer_id)->phone_number;
        $data['phone2'] = Customer::find($sale->customer_id)->phone_number2;
        $data['user'] = User::find($sale->user_id)->name;
        $data['date'] = date('Y-m-d h:i a', strtotime($sale->created_at));
        $products = CustomProducts::where('sale_id',$sale->id)->get();

        foreach($products as $product){
            $pro = [];

            $pro['name'] = $product->name;
            $pro['describtion'] = $product->describtion;
            $pro['qty'] = $product->qty;
            $images = CustomProductImgs::where('product_id',$product->id)->get();
            foreach ($images as $image) {
                $pro['images'][] = $image->image;
            }

            $data['products'][] = $pro;
        }
        return response()->json($data);

    }

    public function updatesaleBySelection(Request $request)
    {

        $sale_id = $request['saleIdArray'];
        $value = $request['value'];
        switch ($value) {
            case '1':
                $text = trans('confirm');
                break;
            case '2':
                $text = trans('file.Due');
                break;
            case '3':
                $text = trans('file.Returned');
                break;
            case '4':
                $text = trans('Ready To Backup');
                break;
            case '5':
                $text = trans('Out of deliverey');
                break;
            case '6':
                $text = trans('Partial collected');
                break;
            case '7':
                $text = trans('Draft');
                break;
                 case '8':
                $text = trans('Delivered');
                break;
            case '10':
                $text = trans('Order Delivered');
                break;
            
        }
        $sales = "";
        foreach ($sale_id as $id) {
             $sale_data = CustomSales::find($id);

                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $texting = 'Custom Sale Status Was Updated By '.$username.' To '.$text;
                DB::table('custom_sale_logs')->insert(['text' => $texting, 'sale_id' => $sale_data->id]);
     
          $update =   $sale_data->update([
           'sale_status' => $value
        ]);
if ($update) {
    $sales .= $sale_data->reference_no.',';
}

        }
return 'Custom Sales With Reference Numbers '.$sales.' Was Set To '.$text.' Successfully';

}

    public function updateShipBySelection(Request $request)
    {

        $sale_id = $request['saleIdArray'];
        $value = $request['value'];
        
        $sales = "";
        foreach ($sale_id as $id) {
             $sale_data = CustomSales::find($id);

                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $texting = 'Custom Sale Shipping Method Was Updated By '.$username.' To '.$value;
                DB::table('sale_logs')->insert(['text' => $texting, 'sale_id' => $sale_data->id]);
           $sale_data->shipping_with = $value;
          $update =   $sale_data->save();
if ($update) {
    $sales .= $sale_data->reference_no.',';
}

        }
return 'Custom Sales With Reference Numbers '.$sales.' Shipping Method Was Set To '.$value.' Successfully';

}

    public function getsalelog($id){
        $data = CustomSales::find($id);
        $text =[];
        $time =[];
        $logs = DB::table('custom_sale_logs')->where('sale_id',$id)->get();
        $text[] = "Custom Sale Was Created By ".User::find($data->user_id)->name;
        $time[] = date("Y-m-d h:i a",strtotime($data->created_at));
        foreach($logs as $log){
            $text[] = $log->text;
            $time[] = date("Y-m-d h:i a",strtotime($log->created_at) + 60*60*2);
        }
        $rtrn_data[] = $text;
        $rtrn_data[] = $time;
        return $rtrn_data;
    }
    public function deleteBySelection(Request $request)
    {

        $sale_id = $request['saleIdArray'];
        $sales = "";
        foreach ($sale_id as $id) {
             $sale_data = CustomSales::find($id);

                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $texting = 'Custom Sale Was Deleted By '.$username;
                DB::table('custom_sale_logs')->insert(['text' => $texting, 'sale_id' => $sale_data->id]);
     
          $update =   $sale_data->update([
           'is_active' => 0
        ]);
if ($update) {
    $sales .= $sale_data->reference_no.',';
}

        }
return 'Custom Sales With Reference Numbers '.$sales.' Was deleted Successfully';

}

public function edit($id){
    $sale = CustomSales::find($id);
    $customer = Customer::find($sale->customer_id);
    $customer_name = $customer->name.' ['.$customer->phone_number.']';
    $products = CustomProducts::where('sale_id',$id)->get();
$address = $customer->state.' - '.$customer->city.' - '.$customer->address;
        $lims_pos_setting_data = Cache::remember('pos_setting', 60*60*24*30, function () {
                return PosSetting::latest()->first();
            });

            $lims_customer_group_all = Cache::remember('customer_group_list', 60*60*24, function () {
                return CustomerGroup::where('is_active', true)->get();
            });

        return view('backend.customsales.edit',compact('sale','products','customer_name','lims_pos_setting_data','lims_customer_group_all','address'));


}
public function updatesale(Request $request){
        $data = $request->except('product_images');

        $sale_id = $data['sale_id'];
        $product_id = $data['product_id'];
        $customer_id = $data['customer_id'];
        $shipping_cost = $data['shipping_cost'];

            $name = $data['product_name'];
            $describtion = $data['product_desc'];
            $qty = $data['product_qty'];
            $images = $request->file('product_images');
$sale = CustomSales::find($sale_id);
$sale->customer_id = $customer_id;
$sale->shipping_cost = $shipping_cost;
$sale->save();
$product = CustomProducts::find($product_id);

$product->name = $name;
$product->describtion = $describtion;
$product->qty = $qty;
$product->save();

                    if($request->hasFile('product_images')) {
                        CustomProductImgs::where('product_id',$product_id)->delete();

            foreach ($images as $key => $image) {
               // return $image;
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis").rand(10000,99999) . 'custom';
                    $imageName = $imageName . '.' . $ext;

                   $sign = $image->move('public/images/product', $imageName);


                    if ($sign) {
                        CustomProductImgs::create([
                            'product_id' => $product_id,
                            'image' => $imageName,
                        ]);
                    }
            }
        }

                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $texting = 'Custom Sale Was Updated By '.$username;
                DB::table('custom_sale_logs')->insert(['text' => $texting, 'sale_id' => $sale_id]);
                            return redirect()->back()->with('success', 'Sale Updated successfully');


}
    public function destroy($id){

        $sale = CustomSales::find($id);

        $sale->is_active = 0;
        $sale->save();

                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $texting = 'Custom Sale Was Deleted By '.$username;
                DB::table('custom_sale_logs')->insert(['text' => $texting, 'sale_id' => $id]);

                            return redirect()->back()->with('success', 'Sale Deleted successfully');

    }
}