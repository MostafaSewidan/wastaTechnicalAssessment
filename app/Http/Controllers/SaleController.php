<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Tax;
use App\Models\Sale;
use App\Models\Delivery;
use App\Models\PosSetting;
use App\Models\Product_Sale;
use App\Models\Product_Warehouse;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\PaymentWithCheque;
use App\Models\PaymentWithGiftCard;
use App\Models\PaymentWithCreditCard;
use App\Models\PaymentWithPaypal;
use App\Models\User;
use App\Models\Variant;
use App\Models\ProductVariant;
use App\Models\CashRegister;
use App\Models\Returns;
use App\Models\ProductReturn;
use App\Models\Expense;
use App\Models\ProductPurchase;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\RewardPointSetting;
use App\Models\CustomField;
use App\Models\Table;
use App\Models\Courier;
use DB;
use Cache;
use App\Models\GeneralSetting;
use App\Models\MailSetting;
use Stripe\Stripe;
use NumberToWords\NumberToWords;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\SaleDetails;
use App\Mail\PaymentDetails;
use Mail;
use Srmklive\PayPal\Services\ExpressCheckout;
use Srmklive\PayPal\Services\AdaptivePayments;
use GeniusTS\HijriDate\Date;
use Illuminate\Support\Facades\Validator;
use App\Models\Currency;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;
use Carbon\Carbon;

class SaleController extends Controller
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

            $lims_gift_card_list = GiftCard::where("is_active", true)->get();
            $lims_pos_setting_data = PosSetting::latest()->first();
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            $lims_courier_list = Courier::where('is_active', true)->get();
            if($lims_pos_setting_data)
                $options = explode(',', $lims_pos_setting_data->payment_options);
            else
                $options = [];
            $numberOfInvoice = Sale::count();
            $custom_fields = CustomField::where([
                                ['belongs_to', 'sale'],
                                ['is_table', true]
                            ])->pluck('name');
            $field_name = [];
            foreach($custom_fields as $fieldName) {
                $field_name[] = str_replace(" ", "_", strtolower($fieldName));
            }
            return view('backend.sale.index', compact('starting_date', 'ending_date', 'warehouse_id', 'sale_status', 'payment_status', 'lims_gift_card_list', 'lims_pos_setting_data', 'lims_reward_point_setting_data', 'lims_account_list', 'lims_warehouse_list', 'all_permission','options', 'numberOfInvoice', 'custom_fields', 'field_name', 'lims_courier_list'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }
    public function uploadcsv(Request $request){

        //get the file
        $upload=$request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        //checking if this is a CSV file
        if($ext != 'csv')
            return redirect()->back()->with('message', 'Please upload a CSV file');

        $filePath=$upload->getRealPath();
             $fileName = date("Ymdhis");
                    $fileName = $fileName . '.' . $ext;
                   $sign = $upload->move('public/documents/sale', $fileName);
        if ($sign) {
            return Redirect::to('/sales?filecsv='.$fileName);
        
    
}
}
    public function getcities($state){
        
    $cities =  DB::table('places')->where('state', $state)->where('company','Basic')->get(['city']);
    return $cities;
    }
    public function saveaddress(Request $request){
     $data = $request->all();
     $address = DB::table('customer_addresses')->insertGetId([
         'customer_id' => $data['customer'] ,
         'address_text' => $data['address_text'],
         'state_text' => $data['state_text'],
         'city_text' => $data['city_text'],
         ]);
     if($address){
         $return['id'] = $address;
         $return['value'] = '( '.$data['state_text'].' - '.$data['city_text'].' - '.$data['address_text'].' )';
         return $return;
     }

    }
    public function getshippingbycustomer($customer){
            $customer_data = Customer::find($customer);    
            $shipping = DB::table('places')->where('state', $customer_data->state)->first();

            return $shipping->shipping + $shipping->fee;

    }
    public function getshipping($state){

            $data =  DB::table('customer_addresses')->where('id', $state)->first();
            $shipping = DB::table('places')->where('state', $data->state_text)->first();

            return $shipping->shipping + $shipping->fee;

    }
    public function getaddress($customer){
    $customer_data = Customer::find($customer);    
    $data =  DB::table('customer_addresses')->where('customer_id', $customer)->get();
    $addresses =[];
    $addresses[] = '<option value="0">( '.$customer_data->state.' - '.$customer_data->city.' - '.$customer_data->address.' )</option>';
    foreach($data as $item){
        $addresses[] = '<option value="'.$item->id.'">( '.$item->state_text.' - '.$item->city_text.' - '.$item->address_text.' )</option>';
    }
    return $addresses;
    }
    public function saleData(Request $request)
    {
       
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
            17 => 'grand_total',
            19 => 'paid_amount',
            22 => 'shipping_with',
        );

        $warehouse_id = $request->input('warehouse_id');
        $sale_status = $request->input('sale_status');
        $print_status = $request->input('print_status');
        $payment_status = $request->input('payment_status');
        $user_id = $request->input('user_id');
        $customer_id = $request->input('customer_id');
        $shipping_method = $request->input('shipping_method');
        $references = $request->input('references');
        $showdeleted = $request->input('showdeleted');
        if(!$showdeleted){
        $q = Sale::whereRaw('sales.is_active = 1')->whereDate('created_at', '>=' ,$request->input('starting_date'))->whereDate('created_at', '<=' ,$request->input('ending_date'));
        }
        else
        {
        $q = Sale::whereRaw('sales.is_active = 0')->whereDate('created_at', '>=' ,$request->input('starting_date'))->whereDate('created_at', '<=' ,$request->input('ending_date'));

        }

            if($print_status == 0){
            $q = $q->where('printed', false);
            }
            elseif($print_status == 1)
            {
            $q = $q->where('printed', true);

            }
       if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('user_id', Auth::id());
        if($sale_status)
            $q = $q->where('sale_status', $sale_status);
       
        if($shipping_method != 0){
        if($shipping_method == 1){
            
            $q = $q->where('shipping_with', '=','')
        ->orWhereNull('shipping_with');
        }
        else
        {
          $q = $q->where('shipping_with',$shipping_method);

        }
        }
       
        if($payment_status)
            $q = $q->where('payment_status', $payment_status);
        if($user_id)
            $q = $q->where('user_id', $user_id);
        if($customer_id)
            $q = $q->where('customer_id', $customer_id);
        
        

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'sales.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        //fetching custom fields data
        $custom_fields = CustomField::where([
                        ['belongs_to', 'sale'],
                        ['is_table', true]
                    ])->pluck('name');
        $field_names = [];
        foreach($custom_fields as $fieldName) {
            $field_names[] = str_replace(" ", "_", strtolower($fieldName));
        }
        if(empty($request->input('search.value'))) {
            if(!$showdeleted){
            $q = Sale::whereRaw('sales.is_active = 1')->with('biller', 'customer', 'warehouse', 'user');
            }
            else
            {
            $q = Sale::whereRaw('sales.is_active = 0')->with('biller', 'customer', 'warehouse', 'user');

            }

            if($print_status == 0){
            $q = $q->where('printed', false);
            }
            elseif($print_status == 1)
            {
            $q = $q->where('printed', true);

            }
            
        if($shipping_method != 0){
        if($shipping_method == 1){
            
            $q = $q->where('shipping_with', '=','')
        ->orWhereNull('shipping_with');
        }
        else
        {
          $q = $q->where('shipping_with',$shipping_method);

        }
        }
        if($user_id)
            $q = $q->where('user_id', $user_id);
        if($customer_id)
            $q = $q->where('customer_id', $customer_id);
                if ($references){
                 $refs = explode(',',$references);
                  $ids = array();
                  foreach($refs as $ref){
                    $singlesale = Sale::where('reference_no',$ref)->first();
                    if(isset($singlesale)){
                    $ids[] = $singlesale->id;
                    }
                  }
           $q =  $q->whereIn('id', $ids);
                }
            $q = $q->whereDate('created_at', '>=' ,$request->input('starting_date'))
                ->whereDate('created_at', '<=' ,$request->input('ending_date'))
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
                $q = $q->where('user_id', Auth::id());
            if($warehouse_id)
                $q = $q->where('warehouse_id', $warehouse_id);
            if($sale_status)
                $q = $q->where('sale_status', $sale_status);
            if($payment_status)
                $q = $q->where('payment_status', $payment_status);
            $sales = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            
            if(!$showdeleted){
            $q = Sale::whereRaw('sales.is_active = 1')->with('biller', 'customer', 'warehouse', 'user');
            }
            else
            {
            $q = Sale::whereRaw('sales.is_active = 0')->with('biller', 'customer', 'warehouse', 'user');

            }
            if($print_status == 0){
            $q = $q->where('printed', false);
            }
            elseif($print_status == 1)
            {
            $q = $q->where('printed', true);

            }
        if($user_id)
            $q = $q->where('user_id', $user_id);
        if($customer_id)
            $q = $q->where('customer_id', $customer_id);
        

        if($shipping_method != 0){
        if($shipping_method == 1){
            
            $q = $q->where('shipping_with', '=','')
        ->orWhereNull('shipping_with');
        }
        else
        {
          $q = $q->where('shipping_with',$shipping_method);

        }
        }
            $q = $q->join('billers', 'sales.biller_id', '=', 'billers.id')
                ->whereDate('sales.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                ->offset($start)
                ->limit($limit)
                ->orderBy($order,$dir);
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $q = $q->select('sales.*')
                        ->with('biller', 'customer', 'warehouse', 'user')
                        ->where('sales.user_id', Auth::id())
                        ->orwhere([
                            ['sales.reference_no', 'LIKE', "%{$search}%"],
                            ['sales.user_id', Auth::id()]
                        ])
                        ->orwhere([
                            ['customers.name', 'LIKE', "%{$search}%"],
                            ['sales.user_id', Auth::id()]
                        ])
                        ->orwhere([
                            ['customers.phone_number', 'LIKE', "%{$search}%"],
                            ['sales.user_id', Auth::id()]
                        ])
                        ->orwhere([
                            ['billers.name', 'LIKE', "%{$search}%"],
                            ['sales.user_id', Auth::id()]
                        ]);
                foreach ($field_names as $key => $field_name) {
                    $q = $q->orwhere([
                            ['sales.user_id', Auth::id()],
                            ['sales.' . $field_name, 'LIKE', "%{$search}%"]
                        ]);
                }


                $sales = $q->get();
                $totalFiltered = $q->count();
            }
            else {
                $q = $q->select('sales.*');

            $q = $q->with('biller', 'customer', 'warehouse', 'user')
                        ->join('customers','sales.customer_id','customers.id')
                        ->orwhere('sales.reference_no', 'LIKE', "%{$search}%")
                        ->orwhere('customers.name', 'LIKE', "%{$search}%")
                        ->orwhere('customers.phone_number', 'LIKE', "%{$search}%")
                        ->orwhere('billers.name', 'LIKE', "%{$search}%");
                foreach ($field_names as $key => $field_name) {
                    $q = $q->orwhere('sales.' . $field_name, 'LIKE', "%{$search}%");
                }

                $sales = $q->get();
                $totalFiltered = $q->count();
            }
        }
        $data = array();
        if(!empty($sales))
        {
            foreach ($sales as $key=>$sale)
            {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date('Y-m-d h:i a', strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $user = User::find($sale->user_id);
                $nestedData['user_name'] = $user->name;
                if(!empty($sale->shipping_with))
                $nestedData['shipping_method'] = $sale->shipping_with;
                else
                $nestedData['shipping_method'] = 'None';

                if($sale->address_id > 0){
                    $address_data = DB::table('customer_addresses')->where('id',$sale->address_id)->first();
                }
              if(!$sale->printed){
               $nestedData['printed'] = '<div class="badge badge-danger">'.trans('file.Not Printed').'</div>';
              }
              else
              {
               $nestedData['printed'] = '<div class="badge badge-success">'.trans('file.Printed').'</div>';
              }
              if($sale->address_id == 0){
                 if(isset($sale->customer->state)){
                $nestedData['state'] = $sale->customer->state;
                 }
                 else
                 {
                $nestedData['state'] = 'customer data error';

                 }
              }
              else
              {
                 $nestedData['state'] =  $address_data->state_text;
              }
              
              if($sale->address_id == 0){

                 if(isset($sale->customer->city)){
                $nestedData['city'] = $sale->customer->city;
                 }
                 else
                 {
                $nestedData['city'] = 'customer data error';

                 }
              }
              else
              {
                 $nestedData['city'] =  $address_data->city_text;
              }

            if(isset($sale->order_discount_value)){
                if($sale->order_discount_type == 'Flat'){
                    $nestedData['discount'] = $sale->order_discount_value;
                }
                else
                {
                    $nestedData['discount'] = $sale->order_discount_value.' %';
                }
            }
            else
            {
                $nestedData['discount'] = 'لا يوجد';
            }
            
              if($sale->address_id == 0){

                 if(isset($sale->customer->address)){
                $nestedData['address'] = $sale->customer->address;
                 }
                 else
                 {
                $nestedData['address'] = 'customer data error';

                 }
              }
              else
              {
                 $nestedData['address'] =  $address_data->address_text;
              }
                 

                 if(isset($sale->biller->name)){
                $nestedData['biller'] = $sale->biller->name;
                 }
                 else
                 {
                $nestedData['biller'] = 'biller data error';

                 }

                 if(isset($sale->customer->phone_number)){
                $nestedData['phone'] = $sale->customer->phone_number;
                 }
                 else
                 {
                $nestedData['phone'] = 'customer data error';

                 }

                 if(isset($sale->customer->phone_number2)){
                $nestedData['phone2'] = $sale->customer->phone_number2;
                 }
                 else
                 {
                $nestedData['phone2'] = 'customer data error';

                 }

                 if(isset($sale->customer->city)){
                $nestedData['customer'] = $sale->customer->name.'<input type="hidden" class="deposit" value="0" />'.'<input type="hidden" class="points" value="0" />';
                 }
                 else
                 {
                $nestedData['customer'] = 'customer data error<input type="hidden" class="deposit" value="0" />'.'<input type="hidden" class="points" value="0" />';

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

                if($sale->payment_status == 1)
                    $nestedData['payment_status'] = '<div class="badge badge-danger">'.trans('file.Pending').'</div>';
                elseif($sale->payment_status == 2)
                    $nestedData['payment_status'] = '<div class="badge badge-danger">'.trans('file.Due').'</div>';
                elseif($sale->payment_status == 3)
                    $nestedData['payment_status'] = '<div class="badge badge-warning">'.trans('file.Partial').'</div>';
                else
                    $nestedData['payment_status'] = '<div class="badge badge-success">'.trans('file.Paid').'</div>';
                $delivery_data = DB::table('deliveries')->select('status')->where('sale_id', $sale->id)->first();
                if($delivery_data) {
                    if($delivery_data->status == 1)
                        $nestedData['delivery_status'] = '<div class="badge badge-info">'.trans('file.Packing').'</div>';
                    elseif($delivery_data->status == 2)
                        $nestedData['delivery_status'] = '<div class="badge badge-info">'.trans('file.Delivering').'</div>';
                    elseif($delivery_data->status == 3)
                        $nestedData['delivery_status'] = '<div class="badge badge-info">'.trans('file.Delivered').'</div>';
                }
                else
                $nestedData['delivery_status'] = 'N/A';
              
                $nestedData['products'] = '';
                $prod_data = Product_Sale::where('sale_id', $sale->id)->get();
                $totalorder = 0;
                $totalcost = 0;
                foreach($prod_data as $prod){
                  $prodcost = $prod->net_unit_price*$prod->qty;
                  $totalorder +=$prodcost;
                  $product = Product::find($prod->product_id);
                  $storecost = $product->cost*$prod->qty;
                  $totalcost += $storecost;
                  $var = Variant::find($prod->variant_id);
                  
                if ($sale->sale_status == 9) {
                    if($prod->damage_reason > 0){
                        $reason = "";
                        switch ($prod->damage_reason) {
                            case '1':
                               $reason = trans('file.Warehouse');
                                break;
                            case '2':
                               $reason = trans('Shipping Company');
                                break;
                            case '3':
                               $reason = trans('Client');
                                break;
                            case '4':
                               $reason = trans('Unknown');
                                break;
                            
                            default:
                               $reason = 'No Damaged';
                                break;
                        }
                    $nestedData['sale_status'] .= '<div class="badge badge-danger">'.$reason.'</div>';
                    }
                }
                  if($var){
                     
                  $nestedData['products'] .= $product->name.' - '.$var->name .' ('. $prod->qty.'),';
                  }
                  else
                  {
                      $var = ProductVariant::where('variant_id',$prod->variant_id)->first();
                      if($var){
                  $nestedData['products'] .= $product->name.' - '.$var->item_code .' ('. $prod->qty.'),';
                      }
                      else
                      {
                  $nestedData['products'] .= $product->name.' ('. $prod->qty.'),';
                      }
                  }
                }
                
                $nestedData['shipping_cost'] = $sale->shipping_cost;
                $nestedData['total_cost'] = $totalcost;
                
            if(isset($sale->order_discount_value)){
                if($sale->order_discount_type == 'Flat'){
                    $nestedData['grand_total'] = number_format(($totalorder + $sale->shipping_cost) - $sale->order_discount_value, config('decimal'));
                }
                else
                {
                    $biggrand = ($totalorder + $sale->shipping_cost);
                    $percent = ($sale->order_discount_value / 100) * $totalorder;
                  $nestedData['grand_total'] = number_format((   $biggrand - $percent), config('decimal'));

                    
                }
            }
            else
            {
                                $nestedData['grand_total'] = number_format($totalorder + $sale->shipping_cost, config('decimal'));

            }
            if(isset($sale->order_discount_value)){
                if($sale->order_discount_type == 'Flat'){
                    $nestedData['paid_amount'] = number_format(($totalorder + $sale->shipping_cost) - $sale->order_discount_value, config('decimal'));
                }
                else
                {
                    $biggrand = ($totalorder + $sale->shipping_cost);
                    $percent = ($sale->order_discount_value / 100) * $totalorder;
                  $nestedData['paid_amount'] = number_format((   $biggrand - $percent), config('decimal'));

                    
                }
            }
            else
            {
                                $nestedData['paid_amount'] = number_format($totalorder + $sale->shipping_cost, config('decimal'));

            }
                $returned_amount = DB::table('returns')->where('sale_id', $sale->id)->sum('grand_total');
                $nestedData['returned_amount'] = number_format($returned_amount, config('decimal'));
                $nestedData['due'] = number_format($sale->grand_total - $returned_amount - $sale->paid_amount, config('decimal'));
                
                //fetching custom fields data
                foreach($field_names as $field_name) {
                    $nestedData[$field_name] = $sale->$field_name;
                }
                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li><a href="'.route('sale.invoice', $sale->id).'" class="btn btn-link"><i class="fa fa-copy"></i> '.trans('file.Generate Invoice').'</a></li>
                                <li>
                                    <button type="button" class="btn btn-link view"><i class="fa fa-eye"></i> '.trans('file.View').'</button>
                                </li>';
                $nestedData['options'] .=
                        '<li>
                            <button type="button" class="viewlog btn btn-link" data-id = "'.$sale->id.'"><i class="fa fa-list"></i> '.trans('file.View Log').'</button>
                        </li>';
                if(in_array("sales-edit", $request['all_permission'])){
                    if($sale->sale_status == 1){
                    if($sale->sale_status != 3)
                        $nestedData['options'] .= '<li>
                            <a href="'.route('sales.edit', $sale->id).'" class="btn btn-link"><i class="dripicons-document-edit"></i> '.trans('file.edit').'</a>
                            </li>';
                    else
                        $nestedData['options'] .= '<li>
                            <a href="'.url('sales/'.$sale->id.'/create').'" class="btn btn-link"><i class="dripicons-document-edit"></i> '.trans('file.edit').'</a>
                        </li>';
                    }
                    else
                    {
                        if(Auth::user()->role_id < 2){
                        if($sale->sale_status != 3)
                        $nestedData['options'] .= '<li>
                            <a href="'.route('sales.edit', $sale->id).'" class="btn btn-link"><i class="dripicons-document-edit"></i> '.trans('file.edit').'</a>
                            </li>';
                    else
                        $nestedData['options'] .= '<li>
                            <a href="'.url('sales/'.$sale->id.'/create').'" class="btn btn-link"><i class="dripicons-document-edit"></i> '.trans('file.edit').'</a>
                        </li>';
                    }
                    }
                }
                if(in_array("sale-payment-index", $request['all_permission']))
                    $nestedData['options'] .=
                        '<li>
                            <button type="button" class="get-payment btn btn-link" data-id = "'.$sale->id.'"><i class="fa fa-money"></i> '.trans('file.View Payment').'</button>
                        </li>';
                if(in_array("sale-payment-add", $request['all_permission']))
                    $nestedData['options'] .=
                        '<li>
                            <button type="button" class="add-payment btn btn-link" data-id = "'.$sale->id.'" data-toggle="modal" data-target="#add-payment"><i class="fa fa-plus"></i> '.trans('file.Add Payment').'</button>
                        </li>';

                $nestedData['options'] .=
                    '<li>
                        <button type="button" class="add-delivery btn btn-link" data-id = "'.$sale->id.'"><i class="fa fa-truck"></i> '.trans('file.Add Delivery').'</button>
                    </li>';
                $nestedData['options'] .=
                    '<li>
                        <a href="'.route('sale.setpartial', $sale->id).'" class="add-delivery btn btn-link" ><i class="fa fa-truck"></i> '.trans('file.Set To Partial Returned').'</a>
                    </li>
                    <li>
                        <a href="'.route('sale.setpartcollect', $sale->id).'" class="add-delivery btn btn-link" ><i class="fa fa-truck"></i> '.trans('file.Set To Partial Collected').'</a>
                    </li>';
                if(in_array("sales-delete", $request['all_permission'])){
                    if(!$showdeleted){
                    $nestedData['options'] .= \Form::open(["route" => ["sales.destroy", $sale->id], "method" => "DELETE"] ).'
                            <li>
                              <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.trans("file.delete").'</button>
                            </li>'.\Form::close().'
                        </ul>
                    </div>';
                    }
                    else
                    {
                     $nestedData['options'] .='
                            <li>
                              <a href="'.route('sale.restore', $sale->id).'" class="btn btn-success text-white" ><i class="dripicons-return"></i> '.trans("file.restore").'</a>
                            </li>
                            <li>
                              <a href="'.route('sale.truncate', $sale->id).'" class="btn btn-danger text-white" ><i class="dripicons-trash"></i> '.trans("file.truncate(delete)").'</a>
                            </li>
                        </ul>
                    </div>';   
                    }
                }
                // data for sale details by one click
                $coupon = Coupon::find($sale->coupon_id);
                if($coupon)
                    $coupon_code = $coupon->code;
                else
                    $coupon_code = null;

                if($sale->currency_id)
                    $currency_code = Currency::select('code')->find($sale->currency_id)->code;
                else
                    $currency_code = 'N/A';

                 if(isset($sale->biller->phone_number)){
                $billerphone = $sale->biller->phone_number;
                 }
                 else
                 {
                $billerphone = 'biller data error';

                 }
                 if(isset($sale->biller->address)){
                $billeraddress = $sale->biller->address;
                 }
                 else
                 {
                $billeraddress = 'biller data error';

                 }
                 if(isset($sale->biller->city)){
                $billercity = $sale->biller->city;
                 }
                 else
                 {
                $billercity = 'biller data error';

                 }
                 if(isset($sale->customer->name)){
                $customername = $sale->customer->name;
                 }
                 else
                 {
                $customername = 'customer data error';

                 }
                 if(isset($sale->customer->phone_number)){
                $customerphone = $sale->customer->phone_number;
                 }
                 else
                 {
                $customerphone = 'customer data error';

                 }
                 if(isset($sale->customer->city)){
                $customercity = $sale->customer->city;
                 }
                 else
                 {
                $customercity = 'customer data error';

                 }
                $nestedData['sale'] = array( '[ "'.date(config('date_format'), strtotime($sale->created_at->toDateString())).'"', ' "'.$sale->reference_no.'"', ' "'.$sale_status.'"', ' "'.$nestedData['biller'].'"', ' "wasta"', ' ""', ' "'.$billerphone.'"', ' "'.$billeraddress.'"', ' "'.$billercity.'"', ' "'.$customername.'"', ' "'.$customerphone.'"', ' ""', ' "'.$customercity.'"', ' "'.$sale->id.'"', ' "'.$sale->total_tax.'"', ' "'.$sale->total_discount.'"', ' "'.$sale->total_price.'"', ' "'.$sale->order_tax.'"', ' "'.$sale->order_tax_rate.'"', ' "'.$sale->order_discount.'"', ' "'.$sale->shipping_cost.'"', ' "'.$sale->grand_total.'"', ' "'.$sale->paid_amount.'"', ' "'.preg_replace('/[\n\r]/', "<br>", $sale->sale_note).'"', ' "'.preg_replace('/[\n\r]/', "<br>", $sale->staff_note).'"', ' "'.$sale->user->name.'"', ' "'.$sale->user->email.'"', ' "'.$sale->warehouse->name.'"', ' "'.$coupon_code.'"', ' "'.$sale->coupon_discount.'"', ' "'.$sale->document.'"', ' "'.$currency_code.'"', ' "'.$sale->exchange_rate.'"]'
                );
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        echo json_encode($json_data);
    }

    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('sales-add')) {
            $lims_customer_list = Customer::where('is_active', true)->get();
            if(Auth::user()->role_id > 2) {
                $lims_warehouse_list = Warehouse::where([
                    ['is_active', true],
                    ['id', Auth::user()->warehouse_id]
                ])->get();
                $lims_biller_list = Biller::where([
                    ['is_active', true],
                    ['id', Auth::user()->biller_id]
                ])->get();
            }
            else {
                $lims_warehouse_list = Warehouse::where('is_active', true)->get();
                $lims_biller_list = Biller::where('is_active', true)->get();
            }

            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_pos_setting_data = PosSetting::latest()->first();
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            if($lims_pos_setting_data)
                $options = explode(',', $lims_pos_setting_data->payment_options);
            else
                $options = [];

            $currency_list = Currency::where('is_active', true)->get();
            $numberOfInvoice = Sale::count();
            $custom_fields = CustomField::where('belongs_to', 'sale')->get();
            $lims_customer_group_all = CustomerGroup::where('is_active', true)->get();
            return view('backend.sale.create',compact('currency_list', 'lims_customer_list', 'lims_warehouse_list', 'lims_biller_list', 'lims_pos_setting_data', 'lims_tax_list', 'lims_reward_point_setting_data','options', 'numberOfInvoice', 'custom_fields', 'lims_customer_group_all'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        //return $data;
        /*DB::beginTransaction();
        try {*/
            if(isset($request->reference_no)) {
                $this->validate($request, [
                    'reference_no' => [
                        'max:191', 'required', 'unique:sales'
                    ],
                ]);
            }

            $data['user_id'] = Auth::id();
            $sale_check = Sale::where("customer_id",$data["customer_id"])->where("total_qty",$data["total_qty"])->where("grand_total",$data["grand_total"])->where('created_at', '>', 
        Carbon::now()->subHours(6)->toDateTimeString()
    )->where('is_active',true)->first();
            if(!$sale_check){
            $cash_register_data = CashRegister::where([
                ['user_id', $data['user_id']],
                ['warehouse_id', $data['warehouse_id']],
                ['status', true]
            ])->first();
            
            if($data['order_discount_type'] == "Percentage"){
                if($data['order_discount_value'] > 10){
                    $data['order_discount_value'] = 10;
                }
            }
      
            if($cash_register_data)
                $data['cash_register_id'] = $cash_register_data->id;

            if(isset($data['created_at']))
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            else
                $data['created_at'] = date("Y-m-d H:i:s");
            //return dd($data);
            if($data['pos']) {
                if(!isset($data['reference_no']))
                    $data['reference_no'] = 'PO' . date("ymd") . '-'. rand(10000,99999).'-'.rand(10000,99999);

                $balance = $data['grand_total'] - $data['paid_amount'];
                if($balance > 0 || $balance < 0)
                    $data['payment_status'] = 2;
                else
                    $data['payment_status'] = 4;

                if($data['draft']) {
                    $lims_sale_data = Sale::find($data['sale_id']);
                    $lims_product_sale_data = Product_Sale::where('sale_id', $data['sale_id'])->get();
                    foreach ($lims_product_sale_data as $product_sale_data) {
                        $product_sale_data->delete();
                    }
                    $lims_sale_data->delete();
                }
            }
            else {
                if(!isset($data['reference_no']))
                    $data['reference_no'] = 'PO' . date("ymd") . '-'. rand(10000,99999).'-'.rand(10000,99999);
            }

            $document = $request->document;
            if ($document) {
                $v = Validator::make(
                    [
                        'extension' => strtolower($request->document->getClientOriginalExtension()),
                    ],
                    [
                        'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                    ]
                );
                if ($v->fails())
                    return redirect()->back()->withErrors($v->errors());

                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if(!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                    $document->move('public/documents/sale', $documentName);
                }
                else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                    $document->move('public/documents/sale', $documentName);
                }
                $data['document'] = $documentName;
            }
            if($data['coupon_active']) {
                $lims_coupon_data = Coupon::find($data['coupon_id']);
                $lims_coupon_data->used += 1;
                $lims_coupon_data->save();
            }
            if(isset($data['table_id'])) {
                $latest_sale = Sale::whereNotNull('table_id')->whereDate('created_at', date('Y-m-d'))->where('warehouse_id', $data['warehouse_id'])->select('queue')->orderBy('id', 'desc')->first();
                if($latest_sale)
                    $data['queue'] = $latest_sale->queue + 1;
                else
                    $data['queue'] = 1;
            }
            //inserting data to sales table
            $lims_sale_data = Sale::create($data);
            //inserting data for custom fields
            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'sale')->select('name', 'type')->get();
            foreach ($custom_fields as $type => $custom_field) {
                $field_name = str_replace(' ', '_', strtolower($custom_field->name));
                if(isset($data[$field_name])) {
                    if($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                        $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                    else
                        $custom_field_data[$field_name] = $data[$field_name];
                }
            }
            if(count($custom_field_data))
                DB::table('sales')->where('id', $lims_sale_data->id)->update($custom_field_data);
            $lims_customer_data = Customer::find($data['customer_id']);
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            //checking if customer gets some points or not
            if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active &&  $data['grand_total'] >= $lims_reward_point_setting_data->minimum_amount) {
                $point = (int)($data['grand_total'] / $lims_reward_point_setting_data->per_point_amount);
                $lims_customer_data->points += $point;
                $lims_customer_data->save();
            }

            //collecting male data
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['reference_no'] = $lims_sale_data->reference_no;
            $mail_data['sale_status'] = $lims_sale_data->sale_status;
            $mail_data['payment_status'] = $lims_sale_data->payment_status;
            $mail_data['total_qty'] = $lims_sale_data->total_qty;
            $mail_data['total_price'] = $lims_sale_data->total_price;
            $mail_data['order_tax'] = $lims_sale_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
            $mail_data['order_discount'] = $lims_sale_data->order_discount;
            $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_sale_data->paid_amount;

            $product_id = $data['product_id'];
            $product_batch_id = $data['product_batch_id'];
            $imei_number = $data['imei_number'];
            $product_code = $data['product_code'];
            $qty = $data['qty'];
            $sale_unit = $data['sale_unit'];
            $net_unit_price = $data['net_unit_price'];
            $discount = $data['discount'];
            $tax_rate = $data['tax_rate'];
            $tax = $data['tax'];
            $total = $data['subtotal'];
            $product_sale = [];

            foreach ($product_id as $i => $id) {
                $lims_product_data = Product::find($id);
                $product_sale['variant_id'] = null;
                $product_sale['product_batch_id'] = null;
                if($lims_product_data->type == 'combo' && $data['sale_status'] == 1){
                    $product_list = explode(",", $lims_product_data->product_list);
                    $variant_list = explode(",", $lims_product_data->variant_list);
                    if($lims_product_data->variant_list)
                        $variant_list = explode(",", $lims_product_data->variant_list);
                    else
                        $variant_list = [];
                    $qty_list = explode(",", $lims_product_data->qty_list);
                    $price_list = explode(",", $lims_product_data->price_list);

                    foreach ($product_list as $key=>$child_id) {
                        $child_data = Product::find($child_id);
                        if(count($variant_list) && $variant_list[$key]) {
                            $child_product_variant_data = ProductVariant::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$key]]
                            ])->first();

                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$key]],
                                ['warehouse_id', $data['warehouse_id'] ],
                            ])->first();

                            $child_product_variant_data->qty -= $qty[$i] * $qty_list[$key];
                            $child_product_variant_data->save();
                        }
                        else {
                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['warehouse_id', $data['warehouse_id'] ],
                            ])->first();
                        }

                        $child_data->qty -= $qty[$i] * $qty_list[$key];
                        $child_warehouse_data->qty -= $qty[$i] * $qty_list[$key];

                        $child_data->save();
                        $child_warehouse_data->save();
                    }
                }

                if($sale_unit[$i] != 'n/a') {
                    $lims_sale_unit_data  = Unit::where('unit_name', $sale_unit[$i])->first();
                    $sale_unit_id = $lims_sale_unit_data->id;
                    if($lims_product_data->is_variant) {
                        $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($id, $product_code[$i])->orderby('variant_id','ASC')->first();
                        $product_sale['variant_id'] = $lims_product_variant_data->variant_id;
                    }
                    if($lims_product_data->is_batch && $product_batch_id[$i]) {
                        $product_sale['product_batch_id'] = $product_batch_id[$i];
                    }

                    if($data['sale_status'] == 1) {
                        if($lims_sale_unit_data->operator == '*')
                            $quantity = $qty[$i] * $lims_sale_unit_data->operation_value;
                        elseif($lims_sale_unit_data->operator == '/')
                            $quantity = $qty[$i] / $lims_sale_unit_data->operation_value;
                        //deduct quantity
                        $lims_product_data->qty = $lims_product_data->qty - $quantity;
                        $lims_product_data->save();
                        //deduct product variant quantity if exist
                        if($lims_product_data->is_variant) {
                            $lims_product_variant_data->qty -= $quantity;
                            $lims_product_variant_data->save();
                            $lims_product_warehouse_data = Product_Warehouse::where('product_id',$id)->where('variant_id',$lims_product_variant_data->variant_id)->where( 'warehouse_id',$data['warehouse_id'])->orwhere('variant_id',$lims_product_variant_data->id)->first();
                        }
                        elseif($product_batch_id[$i]) {
                            $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_batch_id', $product_batch_id[$i] ],
                                ['warehouse_id', $data['warehouse_id'] ]
                            ])->first();
                            $lims_product_batch_data = ProductBatch::find($product_batch_id[$i]);
                            //deduct product batch quantity
                            $lims_product_batch_data->qty -= $quantity;
                            $lims_product_batch_data->save();
                        }
                        else {
                            $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($id, $data['warehouse_id'])->first();
                        }
                        //deduct quantity from warehouse
                        if($lims_product_warehouse_data->qty > 0){
                        $lims_product_warehouse_data->qty -= $quantity;
                        $lims_product_warehouse_data->save();
                        }
                    }
                }
                else
                    $sale_unit_id = 0;

            
                $product_sale['sale_id'] = $lims_sale_data->id ;
                $product_sale['product_id'] = $id;
                $product_sale['imei_number'] = $imei_number[$i];
                $product_sale['qty'] = $mail_data['qty'][$i] = $qty[$i];
                $product_sale['sale_unit_id'] = $sale_unit_id;
                $product_sale['net_unit_price'] = $net_unit_price[$i];
                $product_sale['discount'] = $discount[$i];
                $product_sale['tax_rate'] = $tax_rate[$i];
                $product_sale['tax'] = $tax[$i];
                $product_sale['total'] = $mail_data['total'][$i] = $total[$i];
                Product_Sale::create($product_sale);
            }
            if($data['sale_status'] == 3)
                $message = 'Sale successfully added to draft';
            else
                $message = ' Sale created successfully';

            if($data['payment_status'] == 3 || $data['payment_status'] == 4 || ($data['payment_status'] == 2 && $data['pos'] && $data['paid_amount'] > 0)) {

                $lims_payment_data = new Payment();
                $lims_payment_data->user_id = Auth::id();

                if($data['paid_by_id'] == 1)
                    $paying_method = 'Cash';
                elseif ($data['paid_by_id'] == 2) {
                    $paying_method = 'Gift Card';
                }
                elseif ($data['paid_by_id'] == 3)
                    $paying_method = 'Credit Card';
                elseif ($data['paid_by_id'] == 4)
                    $paying_method = 'Cheque';
                elseif ($data['paid_by_id'] == 5)
                    $paying_method = 'Paypal';
                elseif($data['paid_by_id'] == 6)
                    $paying_method = 'Deposit';
                elseif($data['paid_by_id'] == 7) {
                    $paying_method = 'Points';
                    $lims_payment_data->used_points = $data['used_points'];
                }

                if($cash_register_data)
                    $lims_payment_data->cash_register_id = $cash_register_data->id;
                $lims_account_data = Account::where('is_default', true)->first();
                $lims_payment_data->account_id = $lims_account_data->id;
                $lims_payment_data->sale_id = $lims_sale_data->id;
                $data['payment_reference'] = 'spr-'.date("Ymd").'-'.date("his");
                $lims_payment_data->payment_reference = $data['payment_reference'];
                $lims_payment_data->amount = $data['paid_amount'];
                $lims_payment_data->change = $data['paying_amount'] - $data['paid_amount'];
                $lims_payment_data->paying_method = $paying_method;
                $lims_payment_data->payment_note = $data['payment_note'];
                $lims_payment_data->save();

                $lims_payment_data = Payment::latest()->first();
                $data['payment_id'] = $lims_payment_data->id;
                $lims_pos_setting_data = PosSetting::latest()->first();
                if($paying_method == 'Credit Card' && (strlen($lims_pos_setting_data->stripe_public_key)>0) && (strlen($lims_pos_setting_data->stripe_secret_key )>0)){

                    Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                    $token = $data['stripeToken'];
                    $grand_total = $data['grand_total'];

                    $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('customer_id', $data['customer_id'])->first();

                    if(!$lims_payment_with_credit_card_data) {
                        // Create a Customer:
                        $customer = \Stripe\Customer::create([
                            'source' => $token
                        ]);

                        // Charge the Customer instead of the card:
                        $charge = \Stripe\Charge::create([
                            'amount' => $grand_total * 100,
                            'currency' => 'usd',
                            'customer' => $customer->id
                        ]);
                        $data['customer_stripe_id'] = $customer->id;
                    }
                    else {
                        $customer_id =
                        $lims_payment_with_credit_card_data->customer_stripe_id;

                        $charge = \Stripe\Charge::create([
                            'amount' => $grand_total * 100,
                            'currency' => 'usd',
                            'customer' => $customer_id, // Previously stored, then retrieved
                        ]);
                        $data['customer_stripe_id'] = $customer_id;
                    }
                    $data['charge_id'] = $charge->id;
                    PaymentWithCreditCard::create($data);
                }
                elseif ($paying_method == 'Gift Card') {
                    $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
                    $lims_gift_card_data->expense += $data['paid_amount'];
                    $lims_gift_card_data->save();
                    PaymentWithGiftCard::create($data);
                }
                elseif ($paying_method == 'Cheque') {
                    PaymentWithCheque::create($data);
                }
                elseif ($paying_method == 'Paypal') {
                    $provider = new ExpressCheckout;
                    $paypal_data = [];
                    $paypal_data['items'] = [];
                    foreach ($data['product_id'] as $key => $product_id) {
                        $lims_product_data = Product::find($product_id);
                        $paypal_data['items'][] = [
                            'name' => $lims_product_data->name,
                            'price' => ($data['subtotal'][$key]/$data['qty'][$key]),
                            'qty' => $data['qty'][$key]
                        ];
                    }
                    $paypal_data['items'][] = [
                        'name' => 'Order Tax',
                        'price' => $data['order_tax'],
                        'qty' => 1
                    ];
                    $paypal_data['items'][] = [
                        'name' => 'Order Discount',
                        'price' => $data['order_discount'] * (-1),
                        'qty' => 1
                    ];
                    $paypal_data['items'][] = [
                        'name' => 'Shipping Cost',
                        'price' => $data['shipping_cost'],
                        'qty' => 1
                    ];
                    if($data['grand_total'] != $data['paid_amount']){
                        $paypal_data['items'][] = [
                            'name' => 'Due',
                            'price' => ($data['grand_total'] - $data['paid_amount']) * (-1),
                            'qty' => 1
                        ];
                    }
                    //return $paypal_data;
                    $paypal_data['invoice_id'] = $lims_sale_data->reference_no;
                    $paypal_data['invoice_description'] = "Reference # {$paypal_data['invoice_id']} Invoice";
                    $paypal_data['return_url'] = url('/sale/paypalSuccess');
                    $paypal_data['cancel_url'] = url('/sale/create');

                    $total = 0;
                    foreach($paypal_data['items'] as $item) {
                        $total += $item['price']*$item['qty'];
                    }

                    $paypal_data['total'] = $total;
                    $response = $provider->setExpressCheckout($paypal_data);
                     // This will redirect user to PayPal
                    return redirect($response['paypal_link']);
                }
                elseif($paying_method == 'Deposit'){
                    $lims_customer_data->expense += $data['paid_amount'];
                    $lims_customer_data->save();
                }
                elseif($paying_method == 'Points'){
                    $lims_customer_data->points -= $data['used_points'];
                    $lims_customer_data->save();
                }
            }
        /*}
        catch(Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()]);
        }*/
        if($lims_sale_data->sale_status == '1')
            return redirect('sales/gen_invoice/' . $lims_sale_data->id)->with('message', $message);
        elseif($data['pos'])
            return redirect('pos')->with('message', $message);
        else
            return redirect('sales')->with('message', $message);
            }
            else
            {
                $message = "This Sale Was Created At The Last 6 hours";
            return redirect('pos')->with('message', $message);

            }
    }

    public function sendMail(Request $request)
    {
        $data = $request->all();
        $lims_sale_data = Sale::find($data['sale_id']);
        $lims_product_sale_data = Product_Sale::where('sale_id', $data['sale_id'])->get();
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        $mail_setting = MailSetting::latest()->first();

        if(!$mail_setting) {
            return $this->setErrorMessage('Please Setup Your Mail Credentials First.');
        }
        else if($lims_customer_data->email) {
            //collecting male data
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['reference_no'] = $lims_sale_data->reference_no;
            $mail_data['sale_status'] = $lims_sale_data->sale_status;
            $mail_data['payment_status'] = $lims_sale_data->payment_status;
            $mail_data['total_qty'] = $lims_sale_data->total_qty;
            $mail_data['total_price'] = $lims_sale_data->total_price;
            $mail_data['order_tax'] = $lims_sale_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
            $mail_data['order_discount'] = $lims_sale_data->order_discount;
            $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_sale_data->paid_amount;

            foreach ($lims_product_sale_data as $key => $product_sale_data) {
                $lims_product_data = Product::find($product_sale_data->product_id);
                if($product_sale_data->variant_id) {
                    $variant_data = Variant::select('name')->find($product_sale_data->variant_id);
                    $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
                }
                else
                    $mail_data['products'][$key] = $lims_product_data->name;
                if($lims_product_data->type == 'digital')
                    $mail_data['file'][$key] = url('/public/product/files').'/'.$lims_product_data->file;
                else
                    $mail_data['file'][$key] = '';
                if($product_sale_data->sale_unit_id){
                    $lims_unit_data = Unit::find($product_sale_data->sale_unit_id);
                    $mail_data['unit'][$key] = $lims_unit_data->unit_code;
                }
                else
                    $mail_data['unit'][$key] = '';

                $mail_data['qty'][$key] = $product_sale_data->qty;
                $mail_data['total'][$key] = $product_sale_data->qty;
            }
            $this->setMailInfo($mail_setting);
            try{
                Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
                return $this->setSuccessMessage('Mail sent successfully');
            }
            catch(\Exception $e){
                return $this->setErrorMessage('Please Setup Your Mail Credentials First.');
            }
        }
        else
            return $this->setErrorMessage('Customer doesnt have email!');
    }

    public function paypalSuccess(Request $request)
    {
        $lims_sale_data = Sale::latest()->first();
        $lims_payment_data = Payment::latest()->first();
        $lims_product_sale_data = Product_Sale::where('sale_id', $lims_sale_data->id)->get();
        $provider = new ExpressCheckout;
        $token = $request->token;
        $payerID = $request->PayerID;
        $paypal_data['items'] = [];
        foreach ($lims_product_sale_data as $key => $product_sale_data) {
            $lims_product_data = Product::find($product_sale_data->product_id);
            $paypal_data['items'][] = [
                'name' => $lims_product_data->name,
                'price' => ($product_sale_data->total/$product_sale_data->qty),
                'qty' => $product_sale_data->qty
            ];
        }
        $paypal_data['items'][] = [
            'name' => 'order tax',
            'price' => $lims_sale_data->order_tax,
            'qty' => 1
        ];
        $paypal_data['items'][] = [
            'name' => 'order discount',
            'price' => $lims_sale_data->order_discount * (-1),
            'qty' => 1
        ];
        $paypal_data['items'][] = [
            'name' => 'shipping cost',
            'price' => $lims_sale_data->shipping_cost,
            'qty' => 1
        ];
        if($lims_sale_data->grand_total != $lims_sale_data->paid_amount){
            $paypal_data['items'][] = [
                'name' => 'Due',
                'price' => ($lims_sale_data->grand_total - $lims_sale_data->paid_amount) * (-1),
                'qty' => 1
            ];
        }

        $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
        $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
        $paypal_data['return_url'] = url('/sale/paypalSuccess');
        $paypal_data['cancel_url'] = url('/sale/create');

        $total = 0;
        foreach($paypal_data['items'] as $item) {
            $total += $item['price']*$item['qty'];
        }

        $paypal_data['total'] = $lims_sale_data->paid_amount;
        $response = $provider->getExpressCheckoutDetails($token);
        $response = $provider->doExpressCheckoutPayment($paypal_data, $token, $payerID);
        $data['payment_id'] = $lims_payment_data->id;
        $data['transaction_id'] = $response['PAYMENTINFO_0_TRANSACTIONID'];
        PaymentWithPaypal::create($data);
        return redirect('sales')->with('message', 'Sales created successfully');
    }

    public function paypalPaymentSuccess(Request $request, $id)
    {
        $lims_payment_data = Payment::find($id);
        $provider = new ExpressCheckout;
        $token = $request->token;
        $payerID = $request->PayerID;
        $paypal_data['items'] = [];
        $paypal_data['items'][] = [
            'name' => 'Paid Amount',
            'price' => $lims_payment_data->amount,
            'qty' => 1
        ];
        $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
        $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
        $paypal_data['return_url'] = url('/sale/paypalPaymentSuccess');
        $paypal_data['cancel_url'] = url('/sale');

        $total = 0;
        foreach($paypal_data['items'] as $item) {
            $total += $item['price']*$item['qty'];
        }

        $paypal_data['total'] = $total;
        $response = $provider->getExpressCheckoutDetails($token);
        $response = $provider->doExpressCheckoutPayment($paypal_data, $token, $payerID);
        $data['payment_id'] = $lims_payment_data->id;
        $data['transaction_id'] = $response['PAYMENTINFO_0_TRANSACTIONID'];
        PaymentWithPaypal::create($data);
        return redirect('sales')->with('message', 'Payment created successfully');
    }

    public function getProduct($id)
    {
        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id');
        if(config('without_stock') == 'no') {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', 1],
            ]);
        }
        else {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', 1]
            ]);
        } 
        $lims_product_warehouse_data = $query->whereNull('product_warehouse.variant_id')
                                        ->whereNull('product_warehouse.product_batch_id')
                                        ->select('product_warehouse.*',  'products.is_embeded')
                                        ->get();

        config()->set('database.connections.mysql.strict', false);
        \DB::reconnect(); //important as the existing connection if any would be in strict mode

        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id');

        if(config('without_stock') == 'no') {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', 1],
                
            ]);
        }
        else {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', 1]
            ]);
        }

        $lims_product_with_batch_warehouse_data = $query->whereNull('product_warehouse.variant_id')
        ->whereNotNull('product_warehouse.product_batch_id')
        ->select('product_warehouse.*', 'products.is_embeded')
        ->groupBy('product_warehouse.product_id')
        ->get();

        //now changing back the strict ON
        config()->set('database.connections.mysql.strict', true);
        \DB::reconnect();

        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id');
        if(config('without_stock') == 'no') {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', 1],
            ]);
        }
        else {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', 1],
            ]);
        }
        $lims_product_with_variant_warehouse_data = ProductVariant::join('variants', 'product_variants.variant_id', '=', 'variants.id')
                ->leftjoin('product_warehouse', function($join){
                $join->on('product_variants.variant_id','=','product_warehouse.variant_id')
                ->on('product_variants.product_id','=','product_warehouse.product_id'); // i want to join the users table with either of these columns
                $join->orOn('product_variants.id','=','product_warehouse.variant_id');
            } )
                ->select('variants.name', 'product_variants.*', 'product_warehouse.qty as total_qty')
                ->whereRaw('product_warehouse.qty > 0')
                ->groupby('product_variants.variant_id','product_variants.product_id')
                ->get();
       // $query->whereNotNull('product_warehouse.variant_id')
       // ->select('products.*', 'product_warehouse.*' , 'product_warehouse.qty as total_qty')
       // ->get();

        $product_code = [];
        $product_name = [];
        $product_qty = [];
        $product_type = [];
        $product_id = [];
        $product_list = [];
        $qty_list = [];
        $product_price = [];
        $batch_no = [];
        $product_batch_id = [];
        $expired_date = [];
        $is_embeded = [];
        //product without variant
        foreach ($lims_product_warehouse_data as $product_warehouse)
        {
            $product_qty[] = $product_warehouse->qty;
            $product_price[] = $product_warehouse->price;
            $lims_product_data = Product::find($product_warehouse->product_id);
            $product_code[] =  $lims_product_data->code;
            $product_name[] = htmlspecialchars($lims_product_data->name);
            $product_type[] = $lims_product_data->type;
            $product_id[] = $lims_product_data->id;
            $product_list[] = $lims_product_data->product_list;
            $qty_list[] = $lims_product_data->qty_list;
            $batch_no[] = null;
            $product_batch_id[] = null;
            $expired_date[] = null;
            if($product_warehouse->is_embeded)
                $is_embeded[] = $product_warehouse->is_embeded;
            else
                $is_embeded[] = 0;
        }
        //product with batches
        foreach ($lims_product_with_batch_warehouse_data as $product_warehouse)
        {
            $product_qty[] = $product_warehouse->qty;
            $product_price[] = $product_warehouse->price;
            $lims_product_data = Product::find($product_warehouse->product_id);
            $product_code[] =  $lims_product_data->code;
            $product_name[] = htmlspecialchars($lims_product_data->name);
            $product_type[] = $lims_product_data->type;
            $product_id[] = $lims_product_data->id;
            $product_list[] = $lims_product_data->product_list;
            $qty_list[] = $lims_product_data->qty_list;
            $product_batch_data = ProductBatch::select('id', 'batch_no', 'expired_date')->find($product_warehouse->product_batch_id);
            $batch_no[] = $product_batch_data->batch_no;
            $product_batch_id[] = $product_batch_data->id;
            $expired_date[] = date(config('date_format'), strtotime($product_batch_data->expired_date));
            if($product_warehouse->is_embeded)
                $is_embeded[] = $product_warehouse->is_embeded;
            else
                $is_embeded[] = 0;
        }
        //product with variant
        foreach ($lims_product_with_variant_warehouse_data as $product_warehouse)
        {
            $product_qty[] = $product_warehouse->total_qty;
            $lims_product_data = Product::where('id',$product_warehouse->product_id)->where('is_active', true)->first();
            $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_warehouse->product_id, $product_warehouse->variant_id)->first();
            if($lims_product_variant_data) {
                $product_code[] =  $product_warehouse->item_code;
                $product_name[] = isset($lims_product_data) ? htmlspecialchars($lims_product_data->name) : '';
                $product_type[] = isset($lims_product_data) ? $lims_product_data->type : '';
                $product_id[] = isset($lims_product_data) ? $lims_product_data->id : '';
                $product_list[] = isset($lims_product_data) ? $lims_product_data->product_list : '';
                $qty_list[] = isset($lims_product_data) ? $lims_product_data->qty_list : '';
                $batch_no[] = null;
                $product_batch_id[] = null;
                $expired_date[] = null;
                if($product_warehouse->is_embeded)
                    $is_embeded[] = $product_warehouse->is_embeded;
                else
                    $is_embeded[] = 0;
            }
        }
        //retrieve product with type of digital, combo and service
        $lims_product_data = Product::whereNotIn('type', ['standard'])->where('is_active', true)->get();
        foreach ($lims_product_data as $product)
        {
            $product_qty[] = $product->qty;
            $product_code[] =  $product->code;
            $product_name[] = $product->name;
            $product_type[] = $product->type;
            $product_id[] = $product->id;
            $product_list[] = $product->product_list;
            $qty_list[] = $product->qty_list;
            $batch_no[] = null;
            $product_batch_id[] = null;
            $expired_date[] = null;
            $is_embeded[] = 0;
        }
        $product_data = [$product_code, $product_name, $product_qty, $product_type, $product_id, $product_list, $qty_list, $product_price, $batch_no, $product_batch_id, $expired_date, $is_embeded];
        return $product_data;
    }

    public function posSale()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('sales-add')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';

            $lims_customer_group_all = Cache::remember('customer_group_list', 60*60*24, function () {
                return CustomerGroup::where('is_active', true)->get();
            });
            $lims_warehouse_list = Cache::remember('warehouse_list', 60*60*24*365, function () {
                return Warehouse::where('is_active', true)->get();
            });
            $lims_biller_list = Cache::remember('biller_list', 60*60*24*30, function () {
                return Biller::where('is_active', true)->get();
            });
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $lims_tax_list = Cache::remember('tax_list', 60*60*24*30, function () {
                return Tax::where('is_active', true)->get();
            });
            $lims_product_list = Cache::remember('product_list', 60*60*24, function () {
                return Product::ActiveFeatured()->whereNull('is_variant')->limit(1)->get();
            });
            foreach ($lims_product_list as $key => $product) {
                $images = explode(",", $product->image);
                if($images[0])
                    $product->base_image = $images[0];
                else
                    $product->base_image = 'zummXD2dvAtI.png';
            }
            $lims_product_list_with_variant = Cache::remember('product_list_with_variant', 60*60*24, function () {
                return Product::ActiveFeatured()->whereNotNull('is_variant')->limit(1)->get();
            });

            foreach ($lims_product_list_with_variant as $product) {
                $images = explode(",", $product->image);
                if($images[0])
                    $product->base_image = $images[0];
                else
                    $product->base_image = 'zummXD2dvAtI.png';
                $lims_product_variant_data = $product->variant()->orderBy('position')->get();
                $main_name = $product->name;
                $temp_arr = [];
                foreach ($lims_product_variant_data as $key => $variant) {
                    $product->name = $main_name.' ['.$variant->name.']';
                    $product->code = $variant->pivot['item_code'];
                    $lims_product_list[] = clone($product);
                }
            }

            $product_number = count($lims_product_list);
            $lims_pos_setting_data = Cache::remember('pos_setting', 60*60*24*30, function () {
                return PosSetting::latest()->first();
            });
            if($lims_pos_setting_data)
                $options = explode(',', $lims_pos_setting_data->payment_options);
            else
                $options = [];
            $lims_brand_list = Cache::remember('brand_list', 60*60*24*30, function () {
                return Brand::where('is_active',true)->get();
            });
            $lims_category_list = Cache::remember('category_list', 60*60*24*30, function () {
                return Category::where('is_active',true)->get();
            });
            $lims_table_list = Cache::remember('table_list', 60*60*24*30, function () {
                return Table::where('is_active',true)->get();
            });
            //return $lims_category_list;
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $recent_sale = Sale::select('id','reference_no','customer_id','grand_total','created_at')->where([
                    ['sale_status', 1],
                    ['user_id', Auth::id()]
                ])->orderBy('id', 'desc')->take(10)->get();
                $recent_draft = Sale::select('id','reference_no','customer_id','grand_total','created_at')->where([
                    ['sale_status', 3],
                    ['user_id', Auth::id()]
                ])->orderBy('id', 'desc')->take(10)->get();
            }
            else {
                $recent_sale = Sale::select('id','reference_no','customer_id','grand_total','created_at')->where('sale_status', 1)->orderBy('id', 'desc')->take(10)->get();
                $recent_draft = Sale::select('id','reference_no','customer_id','grand_total','created_at')->where('sale_status', 3)->orderBy('id', 'desc')->take(10)->get();
            }
            $lims_coupon_list = Cache::remember('coupon_list', 60*60*24*30, function () {
                return Coupon::where('is_active',true)->get();
            });
            $flag = 0;

            $currency_list = Currency::where('is_active', true)->get();
            $numberOfInvoice = Sale::count();
            $custom_fields = CustomField::where('belongs_to', 'sale')->get();
            return view('backend.sale.pos', compact('currency_list','role','all_permission', 'lims_customer_group_all', 'lims_warehouse_list', 'lims_reward_point_setting_data', 'lims_product_list', 'product_number', 'lims_tax_list', 'lims_biller_list', 'lims_pos_setting_data', 'options', 'lims_brand_list', 'lims_category_list', 'lims_table_list', 'recent_sale', 'recent_draft', 'lims_coupon_list', 'flag', 'numberOfInvoice', 'custom_fields'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function createSale($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('sales-edit')) {
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $lims_customer_list = Customer::where('is_active', true)->get();
            $lims_customer_group_all = CustomerGroup::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_sale_data = Sale::find($id);
            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            $lims_product_list = Product::where([
                                    ['featured', 1],
                                    ['is_active', true]
                                ])->get();
            foreach ($lims_product_list as $key => $product) {
                $images = explode(",", $product->image);
                if($images[0])
                    $product->base_image = $images[0];
                else
                    $product->base_image = 'zummXD2dvAtI.png';
            }
            $product_number = count($lims_product_list);
            $lims_pos_setting_data = PosSetting::latest()->first();
            $lims_brand_list = Brand::where('is_active',true)->get();
            $lims_category_list = Category::where('is_active',true)->get();
            $lims_coupon_list = Coupon::where('is_active',true)->get();

            $currency_list = Currency::where('is_active', true)->get();

            return view('backend.sale.create_sale',compact('currency_list', 'lims_biller_list', 'lims_customer_list', 'lims_warehouse_list', 'lims_tax_list', 'lims_sale_data','lims_product_sale_data', 'lims_pos_setting_data', 'lims_brand_list', 'lims_category_list', 'lims_coupon_list', 'lims_product_list', 'product_number', 'lims_customer_group_all', 'lims_reward_point_setting_data'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function getProductByFilter($category_id, $brand_id)
    {
        $data = [];
        if(($category_id != 0) && ($brand_id != 0)){
            $lims_product_list = DB::table('products')
                                ->join('categories', 'products.category_id', '=', 'categories.id')
                                ->where([
                                    ['products.is_active', true],
                                    ['products.category_id', $category_id],
                                    ['brand_id', $brand_id]
                                ])->orWhere([
                                    ['categories.parent_id', $category_id],
                                    ['products.is_active', true],
                                    ['brand_id', $brand_id]
                                ])->select('products.name', 'products.code', 'products.image')->get();
        }
        elseif(($category_id != 0) && ($brand_id == 0)){
            $lims_product_list = DB::table('products')
                                ->join('categories', 'products.category_id', '=', 'categories.id')
                                ->where([
                                    ['products.is_active', true],
                                    ['products.category_id', $category_id],
                                ])->orWhere([
                                    ['categories.parent_id', $category_id],
                                    ['products.is_active', true]
                                ])->select('products.id', 'products.name', 'products.code', 'products.image', 'products.is_variant')->get();
        }
        elseif(($category_id == 0) && ($brand_id != 0)){
            $lims_product_list = Product::where([
                                ['brand_id', $brand_id],
                                ['is_active', true]
                            ])
                            ->select('products.id', 'products.name', 'products.code', 'products.image', 'products.is_variant')
                            ->get();
        }
        else
            $lims_product_list = Product::where('is_active', true)->get();

        $index = 0;
        foreach ($lims_product_list as $product) {
            if($product->is_variant) {
                $lims_product_data = Product::select('id')->find($product->id);
                $lims_product_variant_data = $lims_product_data->variant()->orderBy('position')->get();
                foreach ($lims_product_variant_data as $key => $variant) {
                    $data['name'][$index] = $product->name.' ['.$variant->name.']';
                    $data['code'][$index] = $variant->pivot['item_code'];
                    $images = explode(",", $product->image);
                    $data['image'][$index] = $images[0];
                    $index++;
                }
            }
            else {
                $data['name'][$index] = $product->name;
                $data['code'][$index] = $product->code;
                $images = explode(",", $product->image);
                $data['image'][$index] = $images[0];
                $index++;
            }
        }
        return $data;
    }

    public function getFeatured()
    {
        $data = [];
        $lims_product_list = Product::where([
            ['is_active', true],
            ['featured', true]
        ])->select('products.id', 'products.name', 'products.code', 'products.image', 'products.is_variant')->get();

        $index = 0;
        foreach ($lims_product_list as $product) {
            if($product->is_variant) {
                $lims_product_data = Product::select('id')->find($product->id);
                $lims_product_variant_data = $lims_product_data->variant()->orderBy('position')->get();
                foreach ($lims_product_variant_data as $key => $variant) {
                    $data['name'][$index] = $product->name.' ['.$variant->name.']';
                    $data['code'][$index] = $variant->pivot['item_code'];
                    $images = explode(",", $product->image);
                    $data['image'][$index] = $images[0];
                    $index++;
                }
            }
            else {
                $data['name'][$index] = $product->name;
                $data['code'][$index] = $product->code;
                $images = explode(",", $product->image);
                $data['image'][$index] = $images[0];
                $index++;
            }
        }
        return $data;
    }

    public function getCustomerGroup($id)
    {
         $lims_customer_data = Customer::find($id);
         $lims_customer_group_data = CustomerGroup::find($lims_customer_data->customer_group_id);
         return $lims_customer_group_data->percentage;
    }

    public function limsProductSearch(Request $request)
    {
        $todayDate = date('Y-m-d');
        $product_code = explode("(", $request['data']);
        $product_info = explode("?", $request['data']);
        $customer_id = $product_info[1];
        if(strpos($request['data'], '|')) {
            $product_info = explode("|", $request['data']);
            $embeded_code = $product_code[0];
            $product_code[0] = substr($embeded_code, 0, 7);
            $qty = substr($embeded_code, 7, 5) / 1000;
        }
        else {
            $product_code[0] = rtrim($product_code[0], " ");
            $qty = $product_info[2];
        }
        $product_variant_id = null;
        $all_discount = DB::table('discount_plan_customers')
                        ->join('discount_plans', 'discount_plans.id', '=', 'discount_plan_customers.discount_plan_id')
                        ->join('discount_plan_discounts', 'discount_plans.id', '=', 'discount_plan_discounts.discount_plan_id')
                        ->join('discounts', 'discounts.id', '=', 'discount_plan_discounts.discount_id')
                        ->where([
                            ['discount_plans.is_active', true],
                            ['discounts.is_active', true],
                            ['discount_plan_customers.customer_id', $customer_id]
                        ])
                        ->select('discounts.*')
                        ->get();
        $lims_product_data = Product::where([
            ['code', $product_code[0]],
            ['is_active', true]
        ])->first();
        $image = "";
        if(!$lims_product_data) {
            $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.*', 'product_variants.id as product_variant_id', 'product_variants.item_code', 'product_variants.additional_price','product_variants.variant_id')
                ->where([
                    ['product_variants.item_code', $product_code[0]],
                    ['products.is_active', true]
                ])->first();
            $product_variant_id = $lims_product_data->product_variant_id;
            $variant = Variant::find($lims_product_data->variant_id);
            if($variant)
            $image = $variant->image;
        }

        $product[] = $lims_product_data->name;
        if($lims_product_data->is_variant){
            $product[] = $lims_product_data->item_code;
            $lims_product_data->price += $lims_product_data->additional_price;
        }
        else
            $product[] = $lims_product_data->code;

        $no_discount = 1;
        foreach ($all_discount as $key => $discount) {
            $product_list = explode(",", $discount->product_list);
            $days = explode(",", $discount->days);

            if( ( $discount->applicable_for == 'All' || in_array($lims_product_data->id, $product_list) ) && ( $todayDate >= $discount->valid_from && $todayDate <= $discount->valid_till && in_array(date('D'), $days) && $qty >= $discount->minimum_qty && $qty <= $discount->maximum_qty ) ) {
                if($discount->type == 'flat') {
                    $product[] = $lims_product_data->price - $discount->value;
                }
                elseif($discount->type == 'percentage') {
                    $product[] = $lims_product_data->price - ($lims_product_data->price * ($discount->value/100));
                }
                $no_discount = 0;
                break;
            }
            else {
                continue;
            }
        }

        if($lims_product_data->promotion && $todayDate <= $lims_product_data->last_date && $no_discount) {
            $product[] = $lims_product_data->promotion_price;
        }
        elseif($no_discount)
            $product[] = $lims_product_data->price;

        if($lims_product_data->tax_id) {
            $lims_tax_data = Tax::find($lims_product_data->tax_id);
            $product[] = $lims_tax_data->rate;
            $product[] = $lims_tax_data->name;
        }
        else{
            $product[] = 0;
            $product[] = 'No Tax';
        }
        $product[] = $lims_product_data->tax_method;
        if($lims_product_data->type == 'standard'){
            $units = Unit::where("base_unit", $lims_product_data->unit_id)
                    ->orWhere('id', $lims_product_data->unit_id)
                    ->get();
            $unit_name = array();
            $unit_operator = array();
            $unit_operation_value = array();
            foreach ($units as $unit) {
                if($lims_product_data->sale_unit_id == $unit->id) {
                    array_unshift($unit_name, $unit->unit_name);
                    array_unshift($unit_operator, $unit->operator);
                    array_unshift($unit_operation_value, $unit->operation_value);
                }
                else {
                    $unit_name[]  = $unit->unit_name;
                    $unit_operator[] = $unit->operator;
                    $unit_operation_value[] = $unit->operation_value;
                }
            }
            $product[] = implode(",",$unit_name) . ',';
            $product[] = implode(",",$unit_operator) . ',';
            $product[] = implode(",",$unit_operation_value) . ',';
        }
        else{
            $product[] = 'n/a'. ',';
            $product[] = 'n/a'. ',';
            $product[] = 'n/a'. ',';
        }
        $product[] = $lims_product_data->id;
        $product[] = $product_variant_id;
        $product[] = $lims_product_data->promotion;
        $product[] = $lims_product_data->is_batch;
        $product[] = $lims_product_data->is_imei;
        $product[] = $lims_product_data->is_variant;
        $product[] = $qty;
        if(empty($image))
        $image = $lims_product_data->image;
        $product[] = $image;
        return $product;

    }

    public function checkDiscount(Request $request)
    {
        $qty = $request->input('qty');
        $customer_id = $request->input('customer_id');
        $lims_product_data = Product::select('id', 'price', 'promotion', 'promotion_price', 'last_date')->find($request->input('product_id'));
        $todayDate = date('Y-m-d');
        $all_discount = DB::table('discount_plan_customers')
                        ->join('discount_plans', 'discount_plans.id', '=', 'discount_plan_customers.discount_plan_id')
                        ->join('discount_plan_discounts', 'discount_plans.id', '=', 'discount_plan_discounts.discount_plan_id')
                        ->join('discounts', 'discounts.id', '=', 'discount_plan_discounts.discount_id')
                        ->where([
                            ['discount_plans.is_active', true],
                            ['discounts.is_active', true],
                            ['discount_plan_customers.customer_id', $customer_id]
                        ])
                        ->select('discounts.*')
                        ->get();
        $no_discount = 1;
        foreach ($all_discount as $key => $discount) {
            $product_list = explode(",", $discount->product_list);
            $days = explode(",", $discount->days);

            if( ( $discount->applicable_for == 'All' || in_array($lims_product_data->id, $product_list) ) && ( $todayDate >= $discount->valid_from && $todayDate <= $discount->valid_till && in_array(date('D'), $days) && $qty >= $discount->minimum_qty && $qty <= $discount->maximum_qty ) ) {
                if($discount->type == 'flat') {
                    $price = $lims_product_data->price - $discount->value;
                }
                elseif($discount->type == 'percentage') {
                    $price = $lims_product_data->price - ($lims_product_data->price * ($discount->value/100));
                }
                $no_discount = 0;
                break;
            }
            else {
                continue;
            }
        }

        if($lims_product_data->promotion && $todayDate <= $lims_product_data->last_date && $no_discount) {
            $price = $lims_product_data->promotion_price;
        }
        elseif($no_discount)
            $price = $lims_product_data->price;

        $data = [$price, $lims_product_data->promotion];
        return $data;
    }

    public function getGiftCard()
    {
        $gift_card = GiftCard::where("is_active", true)->whereDate('expired_date', '>=', date("Y-m-d"))->get(['id', 'card_no', 'amount', 'expense']);
        return json_encode($gift_card);
    }

    public function productSaleData($id)
    {
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        $product_sale = [];
        foreach ($lims_product_sale_data as $key => $product_sale_data) {
            $product = Product::find($product_sale_data->product_id);
            if($product_sale_data->variant_id) {
                $lims_product_variant_data = Variant::find($product_sale_data->variant_id);
              if($lims_product_variant_data)
                $product->code = $lims_product_variant_data->name;
            }
            $unit_data = Unit::find($product_sale_data->sale_unit_id);
            if($unit_data){
                $unit = $unit_data->unit_code;
            }
            else
                $unit = '';
            if($product_sale_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no')->find($product_sale_data->product_batch_id);
                $product_sale[7][$key] = $product_batch_data->batch_no;
            }
            else
                $product_sale[7][$key] = 'N/A';
          
            $product_sale[0][$key] = $product->name . ' [' . $product->code . ']';
            if($product_sale_data->imei_number)
                $product_sale[0][$key] .= '<br>IMEI or Serial Number: '. $product_sale_data->imei_number;
            $product_sale[1][$key] = $product_sale_data->qty;
            $product_sale[2][$key] = $unit;
            $product_sale[3][$key] = $product_sale_data->tax;
            $product_sale[4][$key] = $product_sale_data->tax_rate;
            $product_sale[5][$key] = $product_sale_data->discount;
            $product_sale[6][$key] = $product_sale_data->net_unit_price * $product_sale_data->qty;
            $product_sale[8][$key] = $product_sale_data->return_qty;
            if(empty($product->image)){
            $product_sale[9][$key] = 'default.jpg';
            }
            else
            {
            $product_sale[9][$key] = $product->image;
            }
          
        }
        return $product_sale;
    }

    public function saleByCsv()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('sales-add')){
            $lims_customer_list = Customer::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $numberOfInvoice = Sale::count();
            return view('backend.sale.import',compact('lims_customer_list', 'lims_warehouse_list', 'lims_biller_list', 'lims_tax_list', 'numberOfInvoice'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function importSale(Request $request)
{
    // اصل ل اللف
    $upload = $request->file('file');
    $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
    $stop = 0;
    $message = "";
    // حق ا إذا ك ها  CSV
    if ($ext != 'csv') {
        return redirect()->back()->with('message', 'Please upload a CSV file');
    }

    $filePath = $upload->getRealPath();
    $file_handle = fopen($filePath, 'r');
    $i = 0;

    while (!feof($file_handle)) {
       $current_line = fgetcsv($file_handle);
            if($current_line && $i > 0){
            // ارج البيانات لدة م ر الحالي
            $invoice_no =  str_replace(",","-",$current_line[0]);
            $customer_name = str_replace(",","-",$current_line[2]);
            $customer_email = str_replace(",","-",$current_line[1]);
            $customer_phone = str_replace(",","-",$current_line[3]);
            $city = str_replace(",","-",$current_line[4]);
            $state = str_replace(",","-",$current_line[5]);
            $address = str_replace(",","-",$current_line[6]);
            $product_name = str_replace(",","-",$current_line[7]);
            $product_variant = str_replace(",","-",$current_line[8]);
            $product_price = str_replace(",","-",$current_line[9]);
            $product_code = str_replace(",","-",$current_line[10]);
            $sku = str_replace(",","-",$current_line[11]);
            $quantity = str_replace(",","-",$current_line[12]);
            $discount = str_replace(",","-",str_replace('%','',$current_line[13]));
            $total_order_cod = str_replace(",","-",$current_line[14]);
            $products_count = str_replace(",","-",$current_line[15]);
            $shipping_cost = str_replace(",","-",$current_line[16]);
              if(!empty($invoice_no)){
              if(empty($products_count)){
                $products_count = 0;
              }
              if(empty($discount)){
                $discount = 0;
              }
              if(empty($customer_email)){
                $customer_email = NULL;
              }

            // ... م ق من صح لبنات ية ...

            // إيجد العم ب  لاس و إنء ع جي ذ ل يتم لعر
            $customer = Customer::firstOrCreate(['phone_number' => $customer_phone], [
    'name' => $customer_name,          
    'email' => $customer_email, // قم بتديد قم اف عي
    'city' => $city,
    'state' => $state,
    'address' => $address,
    'customer_group_id' => 1, // ق  م customer_group_id ماب
    'is_active' => 1, // ق  م customer_group_id ماب
]);
              if(empty($shipping_cost)){
               $shipping_cost = 0; 
              }

$data = [
    'customer_id' => $customer->id, // كد م توفر قي هذا حق
    'warehouse_id' => $request->input('warehouse_id'),
    'biller_id' => $request->input('biller_id'),
    'sale_status' => 1,
    'user_id' => auth()->user()->id,
    'item' => $products_count,
    'total_qty' => 0,
    'order_tax_rate' => 0,
    'shipping_cost' => $shipping_cost,
    
     'total_tax' => 0,
     
     'grand_total' => 0,
     'payment_status' => 0,
    // ... او الخرى
];
$sale = Sale::firstOrCreate(['reference_no' => $invoice_no],$data);

if($discount > 0){
    $sale->order_discount_value = $discount;
    $sale->order_discount = $discount;
    if(str_contains($current_line[13], '%')) {
    $sale->order_discount_type = 'Percentage';
} else {
    $sale->order_discount_type = 'Flat';
}
    $sale->save();
}
if(empty($variant)){
  $isvariant = 0;
}
   else
   {
     $isvariant = 1;
   }
              if(empty($product_code)){
               $product_code = rand(10000000,99999999); 
              }
            // يد لمنج اً ع اسم امغير و ن  جديد ذا  ت اعور
            $product = Product::firstOrCreate([
                'code' => $product_code,
            ], [
                'name' => $product_name,
                'sku' => $sku,
                'variant_option' => json_encode(array('size','color')),
                'variant_value' => json_encode(explode('/',$product_variant)),
                'is_active' => 1 ,
                'is_variant' => $isvariant
                // منك إضاف لم  ليانا اخاة بلت ها
            ]);
              
                $variant = $product_variant;
              if(!empty($variant)){
                $lims_variant_data = Variant::firstOrCreate(['name' => $variant]);
                $lims_variant_data->name = $variant;
                $lims_variant_data->save();
                $lims_product_variant_data = ProductVariant::where('item_code' , $variant.'-'.$product->code)->orderby('variant_id','ASC')->first();
                if(!$lims_product_variant_data){
                $lims_product_variant_data = ProductVariant::FirstOrCreate(['item_code' => $variant.'-'.$product->code],[
                  'product_id'=> $product->id,
                  'variant_id'=> $lims_variant_data->id,
                  'position'=> 1,
                  'item_code'=> $variant,
                  'additional_cost'=> NULL,
                  'additional_price'=> NULL,
                  'qty'=> 0,
                ]);
                }
                
            $warehouse_ids = Warehouse::where('is_active', true)->pluck('id');
            foreach ($warehouse_ids as $warehouse_id) {
               
                        Product_Warehouse::create([
                            "product_id" => $product->id,
                            "variant_id" => $lims_product_variant_data->variant_id,
                            "warehouse_id" => $warehouse_id,
                            "qty" => 10,
                        ]);
                    
            }
              }
              echo 'Please Check Sale ('.$invoice_no.') It May Have New Dublicate Variant ... <a href="'.route('sales.edit', $sale->id).'">CHECK</a><br><hr>';
              $stop = 1;
              $warehouse = Product_Warehouse::where('product_id',$product->id)->where('variant_id',$lims_product_variant_data->variant_id)->first();
              if($warehouse){
                  $warehouse->qty -= $quantity;
                  $warehouse->save();
              }

              $product_sale = new Product_Sale;
              $product_sale->sale_id = $sale->id;
              $product_sale->product_id = $product->id;
              if(!empty($variant)){
              $product_sale->variant_id = $lims_variant_data->id;
              }
              $product_sale->qty = $quantity;
              $product_sale->sale_unit_id = 1;
              $product_sale->net_unit_price = $product_price;
              $product_sale->discount = 0;
              $product_sale->tax_rate = 0;
              $product_sale->tax = 0;
              $product_sale->total = $quantity*$product_price;
              $product_sale->save();
           // تعين قة warehouse_id ي التير $data




              }
            // ... لكو ج للبت ة ...
        }

        $i++;
    }
    
        if($stop == 0){
        return redirect('sales')->with('message', $message);
        }
    }

    public function bulkremove(Request $request)
{
    // اصل ل اللف
    $upload = $request->file('file');
    $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
    $message = "";
    // حق ا إذا ك ها  CSV
    if ($ext != 'csv') {
        return redirect()->back()->with('message', 'Please upload a CSV file');
    }

    $filePath = $upload->getRealPath();
    $file_handle = fopen($filePath, 'r');
    $i = 0;

    while (!feof($file_handle)) {
       $current_line = fgetcsv($file_handle);
            if($current_line && $i >= 0){
            // ارج البيانات لدة م ر الحالي
            $invoice_no =  str_replace(",","-",$current_line[0]);
           
              

$sale = Sale::where(['reference_no' => $invoice_no])->first();
if(isset($sale)){
    $sale->is_active = 0;
    $sale->save();
    
                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $text = 'Sale Was Deleted In Bulk Delete By '.$username;
                DB::table('sale_logs')->insert(['text' => $text, 'sale_id' => $sale->id]);
}

  
}
$i++;

              
            // ... لكو ج للبت ة ...
       
    }
    
        $message = 'Data Removed Successfully';
        return redirect('sales')->with('message', $message);
    }

    public function bulkupdate(Request $request)
{
    // اصل ل اللف
    $upload = $request->file('file');
    $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
    $message = "";
    // حق ا إذا ك ها  CSV
    if ($ext != 'csv') {
        return redirect()->back()->with('message', 'Please upload a CSV file');
    }

    $filePath = $upload->getRealPath();
    $file_handle = fopen($filePath, 'r');
    $i = 0;

    while (!feof($file_handle)) {
       $current_line = fgetcsv($file_handle);
            if($current_line && $i >= 0){
            // ارج البيانات لدة م ر الحالي
            $invoice_no =  str_replace(",","-",$current_line[0]);
            $address =  str_replace(",","-",$current_line[1]);
           
              

$sale = Sale::where(['reference_no' => $invoice_no])->first();
if(isset($sale)){
    $customer = Customer::find($sale->customer_id);
    $customer->address = $address;
    $customer->save();
    
                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $text = 'Sale Customer\'s Address Was Updated In Bulk Update By '.$username;
                DB::table('sale_logs')->insert(['text' => $text, 'sale_id' => $sale->id]);
    
}

  
}
$i++;

              
            // ... لكو ج للبت ة ...
       
    }
    
        $message = 'Data Updated Successfully';
        return redirect('sales')->with('message', $message);
    }

    public function edit($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('sales-edit')){
            $lims_customer_list = Customer::get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_sale_data = Sale::find($id);
            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            if($lims_sale_data->exchange_rate)
                $currency_exchange_rate = $lims_sale_data->exchange_rate;
            else
                $currency_exchange_rate = 1;
            $custom_fields = CustomField::where('belongs_to', 'sale')->get();
            return view('backend.sale.edit',compact('lims_customer_list', 'lims_warehouse_list', 'lims_biller_list', 'lims_tax_list', 'lims_sale_data','lims_product_sale_data', 'currency_exchange_rate', 'custom_fields'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function update(Request $request, $id)
    {
        $data = $request->except('document');
        //return dd($data);
        $qtys = $request->input('qty');
        $document = $request->document;
        $lims_sale_data = Sale::find($id);

        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $this->fileDelete('documents/sale/', $lims_sale_data->document);

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
                $document->move('public/documents/sale', $documentName);
            }
            else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move('public/documents/sale', $documentName);
            }
            $data['document'] = $documentName;
        }
        $balance = $data['grand_total'] - $data['paid_amount'];
        if($balance < 0 || $balance > 0)
            $data['payment_status'] = 2;
        else
            $data['payment_status'] = 4;

        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        $data['created_at'] = date("Y-m-d", strtotime(str_replace("/", "-", $data['created_at'])));
        $product_id = $data['product_id'];
        $imei_number = $data['imei_number'];
        $product_batch_id = $data['product_batch_id'];
        $product_code = $data['product_code'];
        $product_variant_id = $data['product_variant_id'];
        $qty = $data['qty'];
        $sale_unit = $data['sale_unit'];
        $net_unit_price = $data['net_unit_price'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $old_product_id = [];
        $product_sale = [];
        $old_product_variant_id = [];
        foreach ($lims_product_sale_data as  $key => $product_sale_data) {
            $old_product_id[] = $product_sale_data->product_id;
            $old_product_variant_id[] = null;
            $lims_product_data = Product::find($product_sale_data->product_id);

            if( ($lims_sale_data->sale_status == 1) && ($lims_product_data->type == 'combo') ) {
                $product_list = explode(",", $lims_product_data->product_list);
                $variant_list = explode(",", $lims_product_data->variant_list);
                if($lims_product_data->variant_list)
                    $variant_list = explode(",", $lims_product_data->variant_list);
                else
                    $variant_list = [];
                $qty_list = explode(",", $lims_product_data->qty_list);

                foreach ($product_list as $index=>$child_id) {
                    $child_data = Product::find($child_id);
                    if(count($variant_list) && $variant_list[$index]) {
                        $child_product_variant_data = ProductVariant::where([
                            ['product_id', $child_id],
                            ['variant_id', $variant_list[$index]]
                        ])->first();

                        $child_warehouse_data = Product_Warehouse::where([
                            ['product_id', $child_id],
                            ['variant_id', $variant_list[$index]],
                            ['warehouse_id', $lims_sale_data->warehouse_id ],
                        ])->first();

                        $child_product_variant_data->qty += $product_sale_data->qty * $qty_list[$index];
                        $child_product_variant_data->save();
                    }
                    else {
                        $child_warehouse_data = Product_Warehouse::where([
                            ['product_id', $child_id],
                            ['warehouse_id', $lims_sale_data->warehouse_id ],
                        ])->first();
                    }

                    $child_data->qty += $product_sale_data->qty * $qty_list[$index];
                    $child_warehouse_data->qty += $product_sale_data->qty * $qty_list[$index];

                    $child_data->save();
                    $child_warehouse_data->save();
                }
            }
            elseif( ($lims_sale_data->sale_status == 1) && ($product_sale_data->sale_unit_id != 0)) {
                $old_product_qty = $product_sale_data->qty;
                $lims_sale_unit_data = Unit::find($product_sale_data->sale_unit_id);
                if ($lims_sale_unit_data->operator == '*')
                    $old_product_qty = $old_product_qty * $lims_sale_unit_data->operation_value;
                else
                    $old_product_qty = $old_product_qty / $lims_sale_unit_data->operation_value;
                if($product_sale_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)->first();
                    if(!$lims_product_variant_data)
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->where('id',$product_sale_data->variant_id)->first();
                    if(!$lims_product_variant_data)
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->where('item_code',$product_sale_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::where([['product_id',$product_sale_data->product_id],['variant_id', $product_sale_data->variant_id],['warehouse_id', $lims_sale_data->warehouse_id]])
                    ->orWhere('variant_id',$lims_product_variant_data->id)
                    ->first();
                    if(!$lims_product_variant_data){
                    $old_product_variant_id[$key] = $product_sale_data->variant_id;
                    $_variant_id = $product_sale_data->variant_id;
                    }
                    else
                    {
                        $old_product_variant_id[$key] = $lims_product_variant_data->id;
                        $_variant_id = $lims_product_variant_data->id;

                    }
                    $lims_product_variant_data->qty += $old_product_qty;
                    $lims_product_variant_data->save();
                    
            if( !(in_array($_variant_id, $product_variant_id)) ){
                $product_sale_data->delete();
            }
                }
                elseif($product_sale_data->product_batch_id) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_sale_data->product_id],
                        ['product_batch_id', $product_sale_data->product_batch_id],
                        ['warehouse_id', $lims_sale_data->warehouse_id]
                    ])->first();

                    $product_batch_data = ProductBatch::find($product_sale_data->product_batch_id);
                    $product_batch_data->qty += $old_product_qty;
                    $product_batch_data->save();
                }
                else
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_sale_data->product_id, $lims_sale_data->warehouse_id)
                    ->first();
                $lims_product_data->qty += $old_product_qty;
                $lims_product_warehouse_data->qty += $old_product_qty;
                $lims_product_data->save();
                $lims_product_warehouse_data->save();
            }

            if($product_sale_data->imei_number) {
                if($lims_product_warehouse_data->imei_number)
                    $lims_product_warehouse_data->imei_number .= ',' . $product_sale_data->imei_number;
                else
                    $lims_product_warehouse_data->imei_number = $product_sale_data->imei_number;
                $lims_product_warehouse_data->save();
            }
            
            elseif( !(in_array($old_product_id[$key], $product_id)) )
                $product_sale_data->delete();

        }
        foreach ($product_id as $key => $pro_id) {
            $lims_product_data = Product::find($pro_id);
            $product_sale['variant_id'] = null;
            $product_sale['qty'] = $qtys[$key];

            if($lims_product_data->type == 'combo' && $data['sale_status'] == 1) {
                $product_list = explode(",", $lims_product_data->product_list);
                $variant_list = explode(",", $lims_product_data->variant_list);
                if($lims_product_data->variant_list)
                    $variant_list = explode(",", $lims_product_data->variant_list);
                else
                    $variant_list = [];
                $qty_list = explode(",", $lims_product_data->qty_list);

                foreach ($product_list as $index => $child_id) {
                    $child_data = Product::find($child_id);
                    if(count($variant_list) && $variant_list[$index]) {
                        $child_product_variant_data = ProductVariant::where([
                            ['product_id', $child_id],
                            ['variant_id', $variant_list[$index] ],
                        ])->first();

                        $child_warehouse_data = Product_Warehouse::where([
                            ['product_id', $child_id],
                            ['variant_id', $variant_list[$index] ],
                            ['warehouse_id', $data['warehouse_id'] ],
                        ])->first();

                        $child_product_variant_data->qty -= $qty[$key] * $qty_list[$index];
                        $child_product_variant_data->save();
                    }
                    else {
                        $child_warehouse_data = Product_Warehouse::where([
                            ['product_id', $child_id],
                            ['warehouse_id', $data['warehouse_id'] ],
                        ])->first();
                    }


                    $child_data->qty -= $qty[$key] * $qty_list[$index];
                    $child_warehouse_data->qty -= $qty[$key] * $qty_list[$index];

                    $child_data->save();
                    $child_warehouse_data->save();
                }
            }
            if($sale_unit[$key] != 'n/a') {
                $lims_sale_unit_data = Unit::where('unit_name', $sale_unit[$key])->first();
                $sale_unit_id = $lims_sale_unit_data->id;
                if($data['sale_status'] == 1) {
                    $new_product_qty = $qty[$key];
                    if ($lims_sale_unit_data->operator == '*') {
                        $new_product_qty = $new_product_qty * $lims_sale_unit_data->operation_value;
                    } else {
                        $new_product_qty = $new_product_qty / $lims_sale_unit_data->operation_value;
                    }
                    if(true) {
                        $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->where('item_code', $product_code[$key])->first();
                        $lims_product_warehouse_data = Product_Warehouse::where([['product_id',$pro_id],['variant_id', $lims_product_variant_data->variant_id],['warehouse_id', $data['warehouse_id']]])
                    ->orWhere('variant_id',$lims_product_variant_data->id)
                        ->first();

                        $product_sale['variant_id'] = $lims_product_variant_data->variant_id;
                        $lims_product_variant_data->qty -= $new_product_qty;
                        $lims_product_variant_data->save();
                    }
                    elseif($product_batch_id[$key]) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['product_batch_id', $product_batch_id[$key] ],
                            ['warehouse_id', $data['warehouse_id'] ]
                        ])->first();

                        $product_batch_data = ProductBatch::find($product_batch_id[$key]);
                        $product_batch_data->qty -= $new_product_qty;
                        $product_batch_data->save();
                    }
                    else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])
                        ->first();
                    }
                    $lims_product_data->qty -= $new_product_qty;
                    $lims_product_warehouse_data->qty -= $new_product_qty;
                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                }
            }
            else
                $sale_unit_id = 0;

            //deduct imei number if available
            if($imei_number[$key]) {
                $imei_numbers = explode(",", $imei_number[$key]);
                $all_imei_numbers = explode(",", $lims_product_warehouse_data->imei_number);
                foreach ($imei_numbers as $number) {
                    if (($j = array_search($number, $all_imei_numbers)) !== false) {
                        unset($all_imei_numbers[$j]);
                    }
                }
                $lims_product_warehouse_data->imei_number = implode(",", $all_imei_numbers);
                $lims_product_warehouse_data->save();
            }

            //collecting mail data
            if($product_sale['variant_id']) {
                $variant_data = Variant::select('name')->find($product_sale['variant_id']);
                $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
            }
            else
                $mail_data['products'][$key] = $lims_product_data->name;

            if($lims_product_data->type == 'digital')
                $mail_data['file'][$key] = url('/public/product/files').'/'.$lims_product_data->file;
            else
                $mail_data['file'][$key] = '';
            if($sale_unit_id)
                $mail_data['unit'][$key] = $lims_sale_unit_data->unit_code;
            else
                $mail_data['unit'][$key] = '';

            if(!$product_sale['variant_id']){
                $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->where('item_code', $product_code[$key])->first();
                $product_sale['variant_id'] = $lims_product_variant_data->variant_id;

            }
            
            $product_sale['sale_id'] = $id ;
            $product_sale['product_id'] = $pro_id;
            $product_sale['imei_number'] = $imei_number[$key];
            $product_sale['product_batch_id'] = $product_batch_id[$key];
            $product_sale['sale_unit_id'] = $sale_unit_id;
            $product_sale['net_unit_price'] = $net_unit_price[$key];
            $product_sale['discount'] = $discount[$key];
            $product_sale['tax_rate'] = $tax_rate[$key];
            $product_sale['tax'] = $tax[$key];
            $product_sale['total'] = $mail_data['total'][$key] = $total[$key];
            if($product_sale['variant_id'] && in_array($product_variant_id[$key], $old_product_variant_id)) {
              $sale_product =  Product_Sale::where([
                    ['product_id', $pro_id],
                    ['variant_id', $product_sale['variant_id']],
                    ['sale_id', $id]
                ])->first();
                
            $sale_product->sale_unit_id = $product_sale['sale_unit_id'] ;
            $sale_product->net_unit_price = $product_sale['net_unit_price'];
            $sale_product->discount = $product_sale['discount'];
            $sale_product->tax_rate = $product_sale['tax_rate'];
            $sale_product->tax = $product_sale['tax'];
            $sale_product->total = $product_sale['total'];
            $sale_product->qty = $qty[$key];
            $sale_product->save();
            
            }
            elseif( $product_sale['variant_id'] === null && (in_array($pro_id, $old_product_id)) ) {
                Product_Sale::where([
                    ['sale_id', $id],
                    ['product_id', $pro_id]
                ])->update($product_sale);
            }
            else
                Product_Sale::create($product_sale);
        }
        $lims_sale_data->update($data);
        
                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $text = 'Sale Was Updated By '.$username;
                DB::table('sale_logs')->insert(['text' => $text, 'sale_id' => $lims_sale_data->id]);
        //inserting data for custom fields
        $custom_field_data = [];
        $custom_fields = CustomField::where('belongs_to', 'sale')->select('name', 'type')->get();
        foreach ($custom_fields as $type => $custom_field) {
            $field_name = str_replace(' ', '_', strtolower($custom_field->name));
            if(isset($data[$field_name])) {
                if($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                    $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                else
                    $custom_field_data[$field_name] = $data[$field_name];
            }
        }
        if(count($custom_field_data))
            DB::table('sales')->where('id', $lims_sale_data->id)->update($custom_field_data);
        $lims_customer_data = Customer::find($data['customer_id']);
        $message = 'Sale updated successfully';
        //collecting mail data
        $mail_setting = MailSetting::latest()->first();
        if($lims_customer_data->email && $mail_setting) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['reference_no'] = $lims_sale_data->reference_no;
            $mail_data['sale_status'] = $lims_sale_data->sale_status;
            $mail_data['payment_status'] = $lims_sale_data->payment_status;
            $mail_data['total_qty'] = $lims_sale_data->total_qty;
            $mail_data['total_price'] = $lims_sale_data->total_price;
            $mail_data['order_tax'] = $lims_sale_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
            $mail_data['order_discount'] = $lims_sale_data->order_discount;
            $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_sale_data->paid_amount;
            $this->setMailInfo($mail_setting);
            try{
                Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
            }
            catch(\Exception $e){
                $message = 'Sale updated successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }

        return redirect('sales')->with('message', $message);
    }

    public function printLastReciept()
    {
        $sale = Sale::where('sale_status', 1)->latest()->first();
        return redirect()->route('sale.invoice', $sale->id);
    }

    public function genInvoice($id)
    {
        $lims_sale_data = Sale::find($id);
        
                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $text = 'Sale Was Printed In Single Print By '.$username;
                DB::table('sale_logs')->insert(['text' => $text, 'sale_id' => $lims_sale_data->id]);
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        if(cache()->has('biller_list'))
        {
            $lims_biller_data = cache()->get('biller_list')->find($lims_sale_data->biller_id);
        }
        else{
            $lims_biller_data = Biller::find($lims_sale_data->biller_id);
        }
        if(cache()->has('warehouse_list'))
        {
            $lims_warehouse_data = cache()->get('warehouse_list')->find($lims_sale_data->warehouse_id);
        }
        else{
            $lims_warehouse_data = Warehouse::find($lims_sale_data->warehouse_id);
        }

        if(cache()->has('customer_list'))
        {
            $lims_customer_data = cache()->get('customer_list')->find($lims_sale_data->customer_id);
        }
        else{
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        }

        $lims_payment_data = Payment::where('sale_id', $id)->get();
        if(cache()->has('pos_setting'))
        {
            $lims_pos_setting_data = cache()->get('pos_setting');
        }
        else{
            $lims_pos_setting_data = PosSetting::select('invoice_option')->latest()->first();
        }

        $supportedIdentifiers = [
            'al', 'fr_BE', 'pt_BR', 'bg', 'cs', 'dk', 'nl', 'et', 'ka', 'de', 'fr', 'hu', 'id', 'it', 'lt', 'lv',
            'ms', 'fa', 'pl', 'ro', 'sk', 'es', 'ru', 'sv', 'tr', 'tk', 'ua', 'yo'
        ]; //ar, az, ku, mk - not supported

        $defaultLocale = \App::getLocale();
        $numberToWords = new NumberToWords();

        if(in_array($defaultLocale, $supportedIdentifiers))
            $numberTransformer = $numberToWords->getNumberTransformer($defaultLocale);
        else
            $numberTransformer = $numberToWords->getNumberTransformer('en');


        // Old Code
        // $numberToWords = new NumberToWords();
        // if(\App::getLocale() == 'ar' || \App::getLocale() == 'hi' || \App::getLocale() == 'vi' || \App::getLocale() == 'en-gb' || \App::getLocale() == 's_chinese' || \App::getLocale() == 't_chinese')
        //     $numberTransformer = $numberToWords->getNumberTransformer('en');
        // else
        //     $numberTransformer = $numberToWords->getNumberTransformer(\App::getLocale());


        if(config('is_zatca')) {
            //generating base64 TLV format qrtext for qrcode
            $qrText = GenerateQrCode::fromArray([
                new Seller(config('company_name')), // seller name
                new TaxNumber(config('vat_registration_number')), // seller tax number
                new InvoiceDate($lims_sale_data->created_at->toDateString()."T".$lims_sale_data->created_at->toTimeString()), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
                new InvoiceTotalAmount(number_format((float)$lims_sale_data->grand_total, 4, '.', '')), // invoice total amount
                new InvoiceTaxAmount(number_format((float)($lims_sale_data->total_tax+$lims_sale_data->order_tax), 4, '.', '')) // invoice tax amount
                // TODO :: Support others tags
            ])->toBase64();
        }
        else {
            $qrText = $lims_sale_data->reference_no;
        }
        if(is_null($lims_sale_data->exchange_rate))
        {
            $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);
            $currency_code = cache()->get('currency')->code;
        } else {
            $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);
            $sale_currency = DB::table('currencies')->select('code')->where('id',$lims_sale_data->currency_id)->first();
            $currency_code = $sale_currency->code;
        }
        $paying_methods = Payment::where('sale_id', $id)->pluck('paying_method')->toArray();
        $paid_by_info = '';
        foreach ($paying_methods as $key => $paying_method) {
            if($key)
                $paid_by_info .= ', '.$paying_method;
            else
                $paid_by_info = $paying_method;
        }
        $sale_custom_fields = CustomField::where([
                                ['belongs_to', 'sale'],
                                ['is_invoice', true]
                            ])->pluck('name');
        $customer_custom_fields = CustomField::where([
                                ['belongs_to', 'customer'],
                                ['is_invoice', true]
                            ])->pluck('name');
        $product_custom_fields = CustomField::where([
                                ['belongs_to', 'product'],
                                ['is_invoice', true]
                            ])->pluck('name');
        if($lims_pos_setting_data->invoice_option == 'A4') {
            return view('backend.sale.a4_invoice', compact('lims_sale_data', 'currency_code', 'lims_product_sale_data', 'lims_biller_data', 'lims_warehouse_data', 'lims_customer_data', 'lims_payment_data', 'numberInWords', 'paid_by_info', 'sale_custom_fields', 'customer_custom_fields', 'product_custom_fields', 'qrText'));
        }
        else{
            return view('backend.sale.invoice', compact('lims_sale_data', 'currency_code', 'lims_product_sale_data', 'lims_biller_data', 'lims_warehouse_data', 'lims_customer_data', 'lims_payment_data', 'numberInWords', 'sale_custom_fields', 'customer_custom_fields', 'product_custom_fields', 'qrText'));
        }
    }

    public function setpartial($id)
    {
        $lims_sale_data = Sale::find($id);
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        if(cache()->has('biller_list'))
        {
            $lims_biller_data = cache()->get('biller_list')->find($lims_sale_data->biller_id);
        }
        else{
            $lims_biller_data = Biller::find($lims_sale_data->biller_id);
        }
        if(cache()->has('warehouse_list'))
        {
            $lims_warehouse_data = cache()->get('warehouse_list')->find($lims_sale_data->warehouse_id);
        }
        else{
            $lims_warehouse_data = Warehouse::find($lims_sale_data->warehouse_id);
        }

        if(cache()->has('customer_list'))
        {
            $lims_customer_data = cache()->get('customer_list')->find($lims_sale_data->customer_id);
        }
        else{
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        }

        $lims_payment_data = Payment::where('sale_id', $id)->get();
        if(cache()->has('pos_setting'))
        {
            $lims_pos_setting_data = cache()->get('pos_setting');
        }
        else{
            $lims_pos_setting_data = PosSetting::select('invoice_option')->latest()->first();
        }

        $supportedIdentifiers = [
            'al', 'fr_BE', 'pt_BR', 'bg', 'cs', 'dk', 'nl', 'et', 'ka', 'de', 'fr', 'hu', 'id', 'it', 'lt', 'lv',
            'ms', 'fa', 'pl', 'ro', 'sk', 'es', 'ru', 'sv', 'tr', 'tk', 'ua', 'yo'
        ]; //ar, az, ku, mk - not supported

        $defaultLocale = \App::getLocale();
        $numberToWords = new NumberToWords();

        if(in_array($defaultLocale, $supportedIdentifiers))
            $numberTransformer = $numberToWords->getNumberTransformer($defaultLocale);
        else
            $numberTransformer = $numberToWords->getNumberTransformer('en');


        // Old Code
        // $numberToWords = new NumberToWords();
        // if(\App::getLocale() == 'ar' || \App::getLocale() == 'hi' || \App::getLocale() == 'vi' || \App::getLocale() == 'en-gb' || \App::getLocale() == 's_chinese' || \App::getLocale() == 't_chinese')
        //     $numberTransformer = $numberToWords->getNumberTransformer('en');
        // else
        //     $numberTransformer = $numberToWords->getNumberTransformer(\App::getLocale());


        if(config('is_zatca')) {
            //generating base64 TLV format qrtext for qrcode
            $qrText = GenerateQrCode::fromArray([
                new Seller(config('company_name')), // seller name
                new TaxNumber(config('vat_registration_number')), // seller tax number
                new InvoiceDate($lims_sale_data->created_at->toDateString()."T".$lims_sale_data->created_at->toTimeString()), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
                new InvoiceTotalAmount(number_format((float)$lims_sale_data->grand_total, 4, '.', '')), // invoice total amount
                new InvoiceTaxAmount(number_format((float)($lims_sale_data->total_tax+$lims_sale_data->order_tax), 4, '.', '')) // invoice tax amount
                // TODO :: Support others tags
            ])->toBase64();
        }
        else {
            $qrText = $lims_sale_data->reference_no;
        }
        if(is_null($lims_sale_data->exchange_rate))
        {
            $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);
            $currency_code = cache()->get('currency')->code;
        } else {
            $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);
            $sale_currency = DB::table('currencies')->select('code')->where('id',$lims_sale_data->currency_id)->first();
            $currency_code = $sale_currency->code;
        }
        $paying_methods = Payment::where('sale_id', $id)->pluck('paying_method')->toArray();
        $paid_by_info = '';
        foreach ($paying_methods as $key => $paying_method) {
            if($key)
                $paid_by_info .= ', '.$paying_method;
            else
                $paid_by_info = $paying_method;
        }
        $sale_custom_fields = CustomField::where([
                                ['belongs_to', 'sale'],
                                ['is_invoice', true]
                            ])->pluck('name');
        $customer_custom_fields = CustomField::where([
                                ['belongs_to', 'customer'],
                                ['is_invoice', true]
                            ])->pluck('name');
        $product_custom_fields = CustomField::where([
                                ['belongs_to', 'product'],
                                ['is_invoice', true]
                            ])->pluck('name');
            return view('backend.sale.setpartial', compact('lims_sale_data', 'currency_code', 'lims_product_sale_data', 'lims_biller_data', 'lims_warehouse_data', 'lims_customer_data', 'lims_payment_data', 'numberInWords', 'sale_custom_fields', 'customer_custom_fields', 'product_custom_fields', 'qrText'));
        
    }
    public function setpartcollect($id)
    {
        $lims_sale_data = Sale::find($id);
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        if(cache()->has('biller_list'))
        {
            $lims_biller_data = cache()->get('biller_list')->find($lims_sale_data->biller_id);
        }
        else{
            $lims_biller_data = Biller::find($lims_sale_data->biller_id);
        }
        if(cache()->has('warehouse_list'))
        {
            $lims_warehouse_data = cache()->get('warehouse_list')->find($lims_sale_data->warehouse_id);
        }
        else{
            $lims_warehouse_data = Warehouse::find($lims_sale_data->warehouse_id);
        }

        if(cache()->has('customer_list'))
        {
            $lims_customer_data = cache()->get('customer_list')->find($lims_sale_data->customer_id);
        }
        else{
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        }

        $lims_payment_data = Payment::where('sale_id', $id)->get();
        if(cache()->has('pos_setting'))
        {
            $lims_pos_setting_data = cache()->get('pos_setting');
        }
        else{
            $lims_pos_setting_data = PosSetting::select('invoice_option')->latest()->first();
        }

        $supportedIdentifiers = [
            'al', 'fr_BE', 'pt_BR', 'bg', 'cs', 'dk', 'nl', 'et', 'ka', 'de', 'fr', 'hu', 'id', 'it', 'lt', 'lv',
            'ms', 'fa', 'pl', 'ro', 'sk', 'es', 'ru', 'sv', 'tr', 'tk', 'ua', 'yo'
        ]; //ar, az, ku, mk - not supported

        $defaultLocale = \App::getLocale();
        $numberToWords = new NumberToWords();

        if(in_array($defaultLocale, $supportedIdentifiers))
            $numberTransformer = $numberToWords->getNumberTransformer($defaultLocale);
        else
            $numberTransformer = $numberToWords->getNumberTransformer('en');


        // Old Code
        // $numberToWords = new NumberToWords();
        // if(\App::getLocale() == 'ar' || \App::getLocale() == 'hi' || \App::getLocale() == 'vi' || \App::getLocale() == 'en-gb' || \App::getLocale() == 's_chinese' || \App::getLocale() == 't_chinese')
        //     $numberTransformer = $numberToWords->getNumberTransformer('en');
        // else
        //     $numberTransformer = $numberToWords->getNumberTransformer(\App::getLocale());


        if(config('is_zatca')) {
            //generating base64 TLV format qrtext for qrcode
            $qrText = GenerateQrCode::fromArray([
                new Seller(config('company_name')), // seller name
                new TaxNumber(config('vat_registration_number')), // seller tax number
                new InvoiceDate($lims_sale_data->created_at->toDateString()."T".$lims_sale_data->created_at->toTimeString()), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
                new InvoiceTotalAmount(number_format((float)$lims_sale_data->grand_total, 4, '.', '')), // invoice total amount
                new InvoiceTaxAmount(number_format((float)($lims_sale_data->total_tax+$lims_sale_data->order_tax), 4, '.', '')) // invoice tax amount
                // TODO :: Support others tags
            ])->toBase64();
        }
        else {
            $qrText = $lims_sale_data->reference_no;
        }
        if(is_null($lims_sale_data->exchange_rate))
        {
            $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);
            $currency_code = cache()->get('currency')->code;
        } else {
            $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);
            $sale_currency = DB::table('currencies')->select('code')->where('id',$lims_sale_data->currency_id)->first();
            $currency_code = $sale_currency->code;
        }
        $paying_methods = Payment::where('sale_id', $id)->pluck('paying_method')->toArray();
        $paid_by_info = '';
        foreach ($paying_methods as $key => $paying_method) {
            if($key)
                $paid_by_info .= ', '.$paying_method;
            else
                $paid_by_info = $paying_method;
        }
        $sale_custom_fields = CustomField::where([
                                ['belongs_to', 'sale'],
                                ['is_invoice', true]
                            ])->pluck('name');
        $customer_custom_fields = CustomField::where([
                                ['belongs_to', 'customer'],
                                ['is_invoice', true]
                            ])->pluck('name');
        $product_custom_fields = CustomField::where([
                                ['belongs_to', 'product'],
                                ['is_invoice', true]
                            ])->pluck('name');
            return view('backend.sale.setpartcollect', compact('lims_sale_data', 'currency_code', 'lims_product_sale_data', 'lims_biller_data', 'lims_warehouse_data', 'lims_customer_data', 'lims_payment_data', 'numberInWords', 'sale_custom_fields', 'customer_custom_fields', 'product_custom_fields', 'qrText'));
        
    }
    public function updatepartcollect(Request $request){
        $dat = $request->all();
        $data = $request->uncollected_qty;
        $sale = $request->sale_id;
        $reason = $request->uncollect_reason;
        foreach ($data as $key => $value) {
            $prod = Product_Sale::find($key);
            $prod->uncollected_qty = $value;
            $prod->save();
        }
        if (!is_null($reason) && is_array($reason)) {
        foreach ($reason as $key => $value) {
            $prod = Product_Sale::find($key);
            $prod->uncollect_reason = $value;
            $prod->save();
        }
        }
          $sale_data = Sale::find($sale);
          $sale_data->is_partcollected = 1;
          $sale_data->save();
               return back()->with('message', 'successful');
   
    }

    public function updatepart(Request $request){
        $dat = $request->all();
        $data = $request->returned;
        $sale = $request->sale_id;
        $reason = $request->damage_reason;
        $damage = $request->damage;
        $prod = "";
        $quantity = 0;
        foreach ($data as $key => $value) {
            $prod = Product_Sale::find($key);
            $quantity = $prod->qty;
            $newquantity = $quantity - ($value - $prod->return_qty);
            $prod->update(['qty' => $newquantity , 'return_qty' => $value ]);
                   
            $warehouse = Product_Warehouse::where('product_id',$product_sale->product_id)->where('variant_id',$product_sale->variant_id)->first();
if($warehouse){
          $warehouse->qty += $value;

          $warehouse->save();
}
        
                $lims_product_data = Product::find($prod->product_id);

                if($prod->variant_id) {
                    $variant = Variant::select('name')->find($prod->variant_id);
                    if($variant)
                    $variant_name = ' - '.$variant->name;
                    else
                    $variant_name = '';
                }
                else
                    $variant_name = '';
                    $name = $lims_product_data->name.$variant_name;
                    $sale_data = Sale::find($sale);

                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $text = 'Sale Was Set To Partial Collect ('.$name.' - '.$value.' ) By '.$username;
                if($value > 0)
                DB::table('sale_logs')->insert(['text' => $text, 'sale_id' => $sale_data->id]);
        }
        if (!is_null($damage) && is_array($damage)) {
        foreach ($damage as $key => $value) {
            $prod = Product_Sale::find($key);
            $prod->is_damaged = $value;
            $prod->save();


        }
        }
        if (!is_null($reason) && is_array($reason)) {
        foreach ($reason as $key => $value) {
            $prod = Product_Sale::find($key);
            $prod->damage_reason = $value;
            $prod->save();
        }
        }
          $sale_data = Sale::find($sale);
          $sale_data->sale_status = 9;
          $sale_data->save();
        
        
               return back()->with('message', 'successful');
   
    }

public function genInvoicebulk(Request $request)
{
    $ides = $request->input('ides');
    $ids = explode(',', $ides);

    foreach ($ids as $item) {
        $sale = Sale::find($item);

        $user = User::find(auth()->user()->id);
        $username = $user->name;
        $text = 'Sale Was Printed In Bulk Print By ' . $username;
        DB::table('sale_logs')->insert(['text' => $text, 'sale_id' => $sale->id]);

        $sale->printed = 1;
        $sale->save();
    }

    return view('backend.sale.bulkinvoice', compact('ids'));
}

    public function addPayment(Request $request)
    {
        $data = $request->all();
        if(!$data['amount'])
            $data['amount'] = 0.00;

        $lims_sale_data = Sale::find($data['sale_id']);
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        $lims_sale_data->paid_amount += $data['amount'];
        $balance = $lims_sale_data->grand_total - $lims_sale_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_sale_data->payment_status = 2;
        elseif ($balance == 0)
            $lims_sale_data->payment_status = 4;

        if($data['paid_by_id'] == 1)
            $paying_method = 'Cash';
        elseif ($data['paid_by_id'] == 2)
            $paying_method = 'Gift Card';
        elseif ($data['paid_by_id'] == 3)
            $paying_method = 'Credit Card';
        elseif($data['paid_by_id'] == 4)
            $paying_method = 'Cheque';
        elseif($data['paid_by_id'] == 5)
            $paying_method = 'Paypal';
        elseif($data['paid_by_id'] == 6)
            $paying_method = 'Deposit';
        elseif($data['paid_by_id'] == 7)
            $paying_method = 'Points';


        $cash_register_data = CashRegister::where([
            ['user_id', Auth::id()],
            ['warehouse_id', $lims_sale_data->warehouse_id],
            ['status', true]
        ])->first();

        $lims_payment_data = new Payment();
        $lims_payment_data->user_id = Auth::id();
        $lims_payment_data->sale_id = $lims_sale_data->id;
        if($cash_register_data)
            $lims_payment_data->cash_register_id = $cash_register_data->id;
        $lims_payment_data->account_id = $data['account_id'];
        $data['payment_reference'] = 'spr-' . date("Ymd") . '-'. date("his");
        $lims_payment_data->payment_reference = $data['payment_reference'];
        $lims_payment_data->amount = $data['amount'];
        $lims_payment_data->change = $data['paying_amount'] - $data['amount'];
        $lims_payment_data->paying_method = $paying_method;
        $lims_payment_data->payment_note = $data['payment_note'];
        $lims_payment_data->save();
        $lims_sale_data->save();

        $lims_payment_data = Payment::latest()->first();
        $data['payment_id'] = $lims_payment_data->id;

        if($paying_method == 'Gift Card'){
            $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
            $lims_gift_card_data->expense += $data['amount'];
            $lims_gift_card_data->save();
            PaymentWithGiftCard::create($data);
        }
        elseif($paying_method == 'Credit Card'){
            $lims_pos_setting_data = PosSetting::latest()->first();
            Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            $token = $data['stripeToken'];
            $amount = $data['amount'];

            $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('customer_id', $lims_sale_data->customer_id)->first();

            if(!$lims_payment_with_credit_card_data) {
                // Create a Customer:
                $customer = \Stripe\Customer::create([
                    'source' => $token
                ]);

                // Charge the Customer instead of the card:
                $charge = \Stripe\Charge::create([
                    'amount' => $amount * 100,
                    'currency' => 'usd',
                    'customer' => $customer->id,
                ]);
                $data['customer_stripe_id'] = $customer->id;
            }
            else {
                $customer_id =
                $lims_payment_with_credit_card_data->customer_stripe_id;

                $charge = \Stripe\Charge::create([
                    'amount' => $amount * 100,
                    'currency' => 'usd',
                    'customer' => $customer_id, // Previously stored, then retrieved
                ]);
                $data['customer_stripe_id'] = $customer_id;
            }
            $data['customer_id'] = $lims_sale_data->customer_id;
            $data['charge_id'] = $charge->id;
            PaymentWithCreditCard::create($data);
        }
        elseif ($paying_method == 'Cheque') {
            PaymentWithCheque::create($data);
        }
        elseif ($paying_method == 'Paypal') {
            $provider = new ExpressCheckout;
            $paypal_data['items'] = [];
            $paypal_data['items'][] = [
                'name' => 'Paid Amount',
                'price' => $data['amount'],
                'qty' => 1
            ];
            $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
            $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
            $paypal_data['return_url'] = url('/sale/paypalPaymentSuccess/'.$lims_payment_data->id);
            $paypal_data['cancel_url'] = url('/sale');

            $total = 0;
            foreach($paypal_data['items'] as $item) {
                $total += $item['price']*$item['qty'];
            }

            $paypal_data['total'] = $total;
            $response = $provider->setExpressCheckout($paypal_data);
            return redirect($response['paypal_link']);
        }
        elseif ($paying_method == 'Deposit') {
            $lims_customer_data->expense += $data['amount'];
            $lims_customer_data->save();
        }
        elseif ($paying_method == 'Points') {
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $used_points = ceil($data['amount'] / $lims_reward_point_setting_data->per_point_amount);

            $lims_payment_data->used_points = $used_points;
            $lims_payment_data->save();

            $lims_customer_data->points -= $used_points;
            $lims_customer_data->save();
        }
        $message = 'Payment created successfully';
        $mail_setting = MailSetting::latest()->first();
        if($lims_customer_data->email && $mail_setting) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['sale_reference'] = $lims_sale_data->reference_no;
            $mail_data['payment_reference'] = $lims_payment_data->payment_reference;
            $mail_data['payment_method'] = $lims_payment_data->paying_method;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_payment_data->amount;
            $mail_data['currency'] = config('currency');
            $mail_data['due'] = $balance;
            $this->setMailInfo($mail_setting);
            try{
                Mail::to($mail_data['email'])->send(new PaymentDetails($mail_data));
            }
            catch(\Exception $e){
                $message = 'Payment created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }

        }
        return redirect('sales')->with('message', $message);
    }
    public function getsalelog($id){
        $data = Sale::find($id);
        $text =[];
        $time =[];
        $logs = DB::table('sale_logs')->where('sale_id',$id)->get();
        $text[] = "Sale Was Created By ".User::find($data->user_id)->name;
        $time[] = date("Y-m-d h:i a",strtotime($data->created_at));
        foreach($logs as $log){
            $text[] = $log->text;
            $time[] = date("Y-m-d h:i a",strtotime($log->created_at) + 60*60*2);
        }
        $rtrn_data[] = $text;
        $rtrn_data[] = $time;
        return $rtrn_data;
    }

    public function getPayment($id)
    {
        $lims_payment_list = Payment::where('sale_id', $id)->get();
        $date = [];
        $payment_reference = [];
        $paid_amount = [];
        $paying_method = [];
        $payment_id = [];
        $payment_note = [];
        $gift_card_id = [];
        $cheque_no = [];
        $change = [];
        $paying_amount = [];
        $account_name = [];
        $account_id = [];

        foreach ($lims_payment_list as $payment) {
            $date[] = date(config('date_format'), strtotime($payment->created_at->toDateString())) . ' '. $payment->created_at->toTimeString();
            $payment_reference[] = $payment->payment_reference;
            $paid_amount[] = $payment->amount;
            $change[] = $payment->change;
            $paying_method[] = $payment->paying_method;
            $paying_amount[] = $payment->amount + $payment->change;
            if($payment->paying_method == 'Gift Card'){
                $lims_payment_gift_card_data = PaymentWithGiftCard::where('payment_id',$payment->id)->first();
                $gift_card_id[] = $lims_payment_gift_card_data->gift_card_id;
            }
            elseif($payment->paying_method == 'Cheque'){
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id',$payment->id)->first();
                $cheque_no[] = $lims_payment_cheque_data->cheque_no;
            }
            else{
                $cheque_no[] = $gift_card_id[] = null;
            }
            $payment_id[] = $payment->id;
            $payment_note[] = $payment->payment_note;
            $lims_account_data = Account::find($payment->account_id);
            $account_name[] = $lims_account_data->name;
            $account_id[] = $lims_account_data->id;
        }
        $payments[] = $date;
        $payments[] = $payment_reference;
        $payments[] = $paid_amount;
        $payments[] = $paying_method;
        $payments[] = $payment_id;
        $payments[] = $payment_note;
        $payments[] = $cheque_no;
        $payments[] = $gift_card_id;
        $payments[] = $change;
        $payments[] = $paying_amount;
        $payments[] = $account_name;
        $payments[] = $account_id;

        return $payments;
    }

    public function updatePayment(Request $request)
    {
        $data = $request->all();
        //return $data;
        $lims_payment_data = Payment::find($data['payment_id']);
        $lims_sale_data = Sale::find($lims_payment_data->sale_id);
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        //updating sale table
        $amount_dif = $lims_payment_data->amount - $data['edit_amount'];
        $lims_sale_data->paid_amount = $lims_sale_data->paid_amount - $amount_dif;
        $balance = $lims_sale_data->grand_total - $lims_sale_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_sale_data->payment_status = 2;
        elseif ($balance == 0)
            $lims_sale_data->payment_status = 4;
        $lims_sale_data->save();

        if($lims_payment_data->paying_method == 'Deposit') {
            $lims_customer_data->expense -= $lims_payment_data->amount;
            $lims_customer_data->save();
        }
        elseif($lims_payment_data->paying_method == 'Points') {
            $lims_customer_data->points += $lims_payment_data->used_points;
            $lims_customer_data->save();
            $lims_payment_data->used_points = 0;
        }
        if($data['edit_paid_by_id'] == 1)
            $lims_payment_data->paying_method = 'Cash';
        elseif ($data['edit_paid_by_id'] == 2){
            if($lims_payment_data->paying_method == 'Gift Card'){
                $lims_payment_gift_card_data = PaymentWithGiftCard::where('payment_id', $data['payment_id'])->first();

                $lims_gift_card_data = GiftCard::find($lims_payment_gift_card_data->gift_card_id);
                $lims_gift_card_data->expense -= $lims_payment_data->amount;
                $lims_gift_card_data->save();

                $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
                $lims_gift_card_data->expense += $data['edit_amount'];
                $lims_gift_card_data->save();

                $lims_payment_gift_card_data->gift_card_id = $data['gift_card_id'];
                $lims_payment_gift_card_data->save();
            }
            else{
                $lims_payment_data->paying_method = 'Gift Card';
                $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
                $lims_gift_card_data->expense += $data['edit_amount'];
                $lims_gift_card_data->save();
                PaymentWithGiftCard::create($data);
            }
        }
        elseif ($data['edit_paid_by_id'] == 3){
            $lims_pos_setting_data = PosSetting::latest()->first();
            Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            if($lims_payment_data->paying_method == 'Credit Card'){
                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $lims_payment_data->id)->first();

                \Stripe\Refund::create(array(
                  "charge" => $lims_payment_with_credit_card_data->charge_id,
                ));

                $customer_id =
                $lims_payment_with_credit_card_data->customer_stripe_id;

                $charge = \Stripe\Charge::create([
                    'amount' => $data['edit_amount'] * 100,
                    'currency' => 'usd',
                    'customer' => $customer_id
                ]);
                $lims_payment_with_credit_card_data->charge_id = $charge->id;
                $lims_payment_with_credit_card_data->save();
            }
            else{
                $token = $data['stripeToken'];
                $amount = $data['edit_amount'];
                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('customer_id', $lims_sale_data->customer_id)->first();

                if(!$lims_payment_with_credit_card_data) {
                    $customer = \Stripe\Customer::create([
                        'source' => $token
                    ]);

                    $charge = \Stripe\Charge::create([
                        'amount' => $amount * 100,
                        'currency' => 'usd',
                        'customer' => $customer->id,
                    ]);
                    $data['customer_stripe_id'] = $customer->id;
                }
                else {
                    $customer_id =
                    $lims_payment_with_credit_card_data->customer_stripe_id;

                    $charge = \Stripe\Charge::create([
                        'amount' => $amount * 100,
                        'currency' => 'usd',
                        'customer' => $customer_id
                    ]);
                    $data['customer_stripe_id'] = $customer_id;
                }
                $data['customer_id'] = $lims_sale_data->customer_id;
                $data['charge_id'] = $charge->id;
                PaymentWithCreditCard::create($data);
            }
            $lims_payment_data->paying_method = 'Credit Card';
        }
        elseif($data['edit_paid_by_id'] == 4){
            if($lims_payment_data->paying_method == 'Cheque'){
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $data['payment_id'])->first();
                $lims_payment_cheque_data->cheque_no = $data['edit_cheque_no'];
                $lims_payment_cheque_data->save();
            }
            else{
                $lims_payment_data->paying_method = 'Cheque';
                $data['cheque_no'] = $data['edit_cheque_no'];
                PaymentWithCheque::create($data);
            }
        }
        elseif($data['edit_paid_by_id'] == 5){
            //updating payment data
            $lims_payment_data->amount = $data['edit_amount'];
            $lims_payment_data->paying_method = 'Paypal';
            $lims_payment_data->payment_note = $data['edit_payment_note'];
            $lims_payment_data->save();

            $provider = new ExpressCheckout;
            $paypal_data['items'] = [];
            $paypal_data['items'][] = [
                'name' => 'Paid Amount',
                'price' => $data['edit_amount'],
                'qty' => 1
            ];
            $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
            $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
            $paypal_data['return_url'] = url('/sale/paypalPaymentSuccess/'.$lims_payment_data->id);
            $paypal_data['cancel_url'] = url('/sale');

            $total = 0;
            foreach($paypal_data['items'] as $item) {
                $total += $item['price']*$item['qty'];
            }

            $paypal_data['total'] = $total;
            $response = $provider->setExpressCheckout($paypal_data);
            return redirect($response['paypal_link']);
        }
        elseif($data['edit_paid_by_id'] == 6){
            $lims_payment_data->paying_method = 'Deposit';
            $lims_customer_data->expense += $data['edit_amount'];
            $lims_customer_data->save();
        }
        elseif($data['edit_paid_by_id'] == 7) {
            $lims_payment_data->paying_method = 'Points';
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $used_points = ceil($data['edit_amount'] / $lims_reward_point_setting_data->per_point_amount);
            $lims_payment_data->used_points = $used_points;
            $lims_customer_data->points -= $used_points;
            $lims_customer_data->save();
        }
        //updating payment data
        $lims_payment_data->account_id = $data['account_id'];
        $lims_payment_data->amount = $data['edit_amount'];
        $lims_payment_data->change = $data['edit_paying_amount'] - $data['edit_amount'];
        $lims_payment_data->payment_note = $data['edit_payment_note'];
        $lims_payment_data->save();
        $message = 'Payment updated successfully';
        //collecting male data
        $mail_setting = MailSetting::latest()->first();
        if($lims_customer_data->email && $mail_setting) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['sale_reference'] = $lims_sale_data->reference_no;
            $mail_data['payment_reference'] = $lims_payment_data->payment_reference;
            $mail_data['payment_method'] = $lims_payment_data->paying_method;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_payment_data->amount;
            $mail_data['currency'] = config('currency');
            $mail_data['due'] = $balance;
            $this->setMailInfo($mail_setting);
            try{
                Mail::to($mail_data['email'])->send(new PaymentDetails($mail_data));
            }
            catch(\Exception $e){
                $message = 'Payment updated successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }
        return redirect('sales')->with('message', $message);
    }

    public function deletePayment(Request $request)
    {
        $lims_payment_data = Payment::find($request['id']);
        $lims_sale_data = Sale::where('id', $lims_payment_data->sale_id)->first();
        $lims_sale_data->paid_amount -= $lims_payment_data->amount;
        $balance = $lims_sale_data->grand_total - $lims_sale_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_sale_data->payment_status = 2;
        elseif ($balance == 0)
            $lims_sale_data->payment_status = 4;
        $lims_sale_data->save();

        if ($lims_payment_data->paying_method == 'Gift Card') {
            $lims_payment_gift_card_data = PaymentWithGiftCard::where('payment_id', $request['id'])->first();
            $lims_gift_card_data = GiftCard::find($lims_payment_gift_card_data->gift_card_id);
            $lims_gift_card_data->expense -= $lims_payment_data->amount;
            $lims_gift_card_data->save();
            $lims_payment_gift_card_data->delete();
        }
        elseif($lims_payment_data->paying_method == 'Credit Card'){
            $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $request['id'])->first();
            $lims_pos_setting_data = PosSetting::latest()->first();
            Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            \Stripe\Refund::create(array(
              "charge" => $lims_payment_with_credit_card_data->charge_id,
            ));

            $lims_payment_with_credit_card_data->delete();
        }
        elseif ($lims_payment_data->paying_method == 'Cheque') {
            $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $request['id'])->first();
            $lims_payment_cheque_data->delete();
        }
        elseif ($lims_payment_data->paying_method == 'Paypal') {
            $lims_payment_paypal_data = PaymentWithPaypal::where('payment_id', $request['id'])->first();
            if($lims_payment_paypal_data){
                $provider = new ExpressCheckout;
                $response = $provider->refundTransaction($lims_payment_paypal_data->transaction_id);
                $lims_payment_paypal_data->delete();
            }
        }
        elseif ($lims_payment_data->paying_method == 'Deposit'){
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
            $lims_customer_data->expense -= $lims_payment_data->amount;
            $lims_customer_data->save();
        }
        elseif ($lims_payment_data->paying_method == 'Points'){
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
            $lims_customer_data->points += $lims_payment_data->used_points;
            $lims_customer_data->save();
        }
        $lims_payment_data->delete();
        return redirect('sales')->with('not_permitted', 'Payment deleted successfully');
    }

    public function todaySale()
    {
        $data['total_sale_amount'] = Sale::whereDate('created_at', date("Y-m-d"))->sum('grand_total');
        $data['total_payment'] = Payment::whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['cash_payment'] = Payment::where([
                                    ['paying_method', 'Cash']
                                ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['credit_card_payment'] = Payment::where([
                                    ['paying_method', 'Credit Card']
                                ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['gift_card_payment'] = Payment::where([
                                    ['paying_method', 'Gift Card']
                                ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['deposit_payment'] = Payment::where([
                                    ['paying_method', 'Deposit']
                                ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['cheque_payment'] = Payment::where([
                                    ['paying_method', 'Cheque']
                                ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['paypal_payment'] = Payment::where([
                                    ['paying_method', 'Paypal']
                                ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['total_sale_return'] = Returns::whereDate('created_at', date("Y-m-d"))->sum('grand_total');
        $data['total_expense'] = Expense::whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['total_cash'] = $data['total_payment'] - ($data['total_sale_return'] + $data['total_expense']);
        return $data;
    }

    public function todayProfit($warehouse_id)
    {
        if($warehouse_id == 0)
            $product_sale_data = Product_Sale::select(DB::raw('product_id, product_batch_id, sum(qty) as sold_qty, sum(total) as sold_amount'))->whereDate('created_at', date("Y-m-d"))->groupBy('product_id', 'product_batch_id')->get();
        else
            $product_sale_data = Sale::join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
            ->select(DB::raw('product_sales.product_id, product_sales.product_batch_id, sum(product_sales.qty) as sold_qty, sum(product_sales.total) as sold_amount'))
            ->where('sales.warehouse_id', $warehouse_id)->whereDate('sales.created_at', date("Y-m-d"))
            ->groupBy('product_sales.product_id', 'product_sales.product_batch_id')->get();

        $product_revenue = 0;
        $product_cost = 0;
        $profit = 0;
        foreach ($product_sale_data as $key => $product_sale) {
            if($warehouse_id == 0) {
                if($product_sale->product_batch_id)
                    $product_purchase_data = ProductPurchase::where([
                        ['product_id', $product_sale->product_id],
                        ['product_batch_id', $product_sale->product_batch_id]
                    ])->get();
                else
                    $product_purchase_data = ProductPurchase::where('product_id', $product_sale->product_id)->get();
            }
            else {
                if($product_sale->product_batch_id) {
                    $product_purchase_data = Purchase::join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                    ->where([
                        ['product_purchases.product_id', $product_sale->product_id],
                        ['product_purchases.product_batch_id', $product_sale->product_batch_id],
                        ['purchases.warehouse_id', $warehouse_id]
                    ])->select('product_purchases.*')->get();
                }
                else
                    $product_purchase_data = Purchase::join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                    ->where([
                        ['product_purchases.product_id', $product_sale->product_id],
                        ['purchases.warehouse_id', $warehouse_id]
                    ])->select('product_purchases.*')->get();
            }

            $purchased_qty = 0;
            $purchased_amount = 0;
            $sold_qty = $product_sale->sold_qty;
            $product_revenue += $product_sale->sold_amount;
            foreach ($product_purchase_data as $key => $product_purchase) {
                $purchased_qty += $product_purchase->qty;
                $purchased_amount += $product_purchase->total;
                if($purchased_qty >= $sold_qty) {
                    $qty_diff = $purchased_qty - $sold_qty;
                    $unit_cost = $product_purchase->total / $product_purchase->qty;
                    $purchased_amount -= ($qty_diff * $unit_cost);
                    break;
                }
            }

            $product_cost += $purchased_amount;
            $profit += $product_sale->sold_amount - $purchased_amount;
        }

        $data['product_revenue'] = $product_revenue;
        $data['product_cost'] = $product_cost;
        if($warehouse_id == 0)
            $data['expense_amount'] = Expense::whereDate('created_at', date("Y-m-d"))->sum('amount');
        else
            $data['expense_amount'] = Expense::where('warehouse_id', $warehouse_id)->whereDate('created_at', date("Y-m-d"))->sum('amount');

        $data['profit'] = $profit - $data['expense_amount'];
        return $data;
    }

    public function updateShipBySelection(Request $request)
    {

        $sale_id = $request['saleIdArray'];
        $value = $request['value'];
        
        $sales = "";
        foreach ($sale_id as $id) {
             $sale_data = Sale::find($id);

                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $texting = 'Sale Shipping Method Was Updated By '.$username.' To '.$value;
                DB::table('sale_logs')->insert(['text' => $texting, 'sale_id' => $sale_data->id]);
           $sale_data->shipping_with = $value;
          $update =   $sale_data->save();
if ($update) {
    $sales .= $sale_data->reference_no.',';
}

        }
return 'Sales With Reference Numbers '.$sales.' Shipping Method Was Set To '.$value.' Successfully';

}
    public function updatepaymentBySelection(Request $request)
    {

        $sale_id = $request['saleIdArray'];
        $value = $request['value'];
        switch ($value) {
            case '1':
                $text = trans('confirm');
                break;
            case '2':
                $text = trans('file.Pending');
                break;
            case '3':
                $text = trans('file.Due');
                break;
            case '4':
                $text = trans('file.Partial');
                break;
            case '5':
                $text = trans('file.Paid');
                break;
            
            
            
        }
        $sales = "";
        foreach ($sale_id as $id) {
             $sale_data = Sale::find($id);

                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $texting = 'Sale Payment Status Was Updated By '.$username.' To '.$text;
                DB::table('sale_logs')->insert(['text' => $texting, 'sale_id' => $sale_data->id]);
          $update =   $sale_data->update([
           'payment_status' => $value
        ]);
if ($update) {
    $sales .= $sale_data->reference_no.',';
}

        }
return 'Sales With Reference Numbers '.$sales.' Was Set To '.$text.' Successfully';

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
             $sale_data = Sale::find($id);

                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $texting = 'Sale Status Was Updated By '.$username.' To '.$text;
                DB::table('sale_logs')->insert(['text' => $texting, 'sale_id' => $sale_data->id]);
      if($value == 3){
        if($sale_data->sale_status !== 3){
        $product_sale_data = Product_Sale::where('sale_id', $id)->get();
        foreach($product_sale_data as $product_sale){
            $warehouse = Product_Warehouse::where('product_id',$product_sale->product_id)->where('variant_id',$product_sale->variant_id)->first();
          $product = Product::find($product_sale->product_id);
          $product->qty += $product_sale->qty;
          if($warehouse){
          $warehouse->qty += $product_sale->qty;
          $warehouse->save();
          }
          else
          {
            $variant_data = ProductVariant::select('item_code')->where('variant_id',$product_sale->variant_id)->where('product_id',$product_sale->product_id)->first();
                      if($variant_data){
                      $variant_id = ProductVariant::select('variant_id')->where('item_code',$variant_data->item_code)->orderby('variant_id','ASC')->first();
                      
           $warehouse = Product_Warehouse::where('product_id',$product_sale->product_id)->where('variant_id',$variant_id->variant_id)->first();
            if($warehouse){
          $warehouse->qty += $product_sale->qty;
          $warehouse->save();
          }
}
          }
          $product->save();
        }
      }
      }
      if($sale_data->sale_status == 3){
          if($value !== 3){
                      $product_sale_data = Product_Sale::where('sale_id', $id)->get();

        foreach($product_sale_data as $product_sale){

            $warehouse = Product_Warehouse::where('product_id',$product_sale->product_id)->where('variant_id',$product_sale->variant_id)->first();
          if($warehouse){
          $warehouse->qty -= $product_sale->qty;
          $warehouse->save();
          }
          else
          {
            $variant_data = ProductVariant::select('item_code')->where('variant_id',$product_sale->variant_id)->where('product_id',$product_sale->product_id)->first();
                      if($variant_data){
                      $variant_id = ProductVariant::select('variant_id')->where('item_code',$variant_data->item_code)->orderby('variant_id','ASC')->first();
                      
           $warehouse = Product_Warehouse::where('product_id',$product_sale->product_id)->where('variant_id',$variant_id->variant_id)->first();
            if($warehouse){
          $warehouse->qty -= $product_sale->qty;
          $warehouse->save();
          }
}
          }

        }
          }
      }
          $update =   $sale_data->update([
           'sale_status' => $value
        ]);
if ($update) {
    $sales .= $sale_data->reference_no.',';
}

        }
return 'Sales With Reference Numbers '.$sales.' Was Set To '.$text.' Successfully';

}
    public function deleteBySelection(Request $request)
    {
        $sale_id = $request['saleIdArray'];
        foreach ($sale_id as $id) {
            $lims_sale_data = Sale::find($id);
            $return_ids = Returns::where('sale_id', $id)->pluck('id')->toArray();
            if(count($return_ids)) {
                ProductReturn::whereIn('return_id', $return_ids)->delete();
                Returns::whereIn('id', $return_ids)->delete();
            }
            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            $lims_delivery_data = Delivery::where('sale_id',$id)->first();
            if($lims_sale_data->sale_status == 3)
                $message = 'Draft deleted successfully';
            else
                $message = 'Sale deleted successfully';
            foreach ($lims_product_sale_data as $product_sale) {
                $lims_product_data = Product::find($product_sale->product_id);
                //adjust product quantity
                if( ($lims_sale_data->sale_status == 1) && ($lims_product_data->type == 'combo') ){
                    $product_list = explode(",", $lims_product_data->product_list);
                    if($lims_product_data->variant_list)
                        $variant_list = explode(",", $lims_product_data->variant_list);
                    else
                        $variant_list = [];
                    $qty_list = explode(",", $lims_product_data->qty_list);

                    foreach ($product_list as $index=>$child_id) {
                        $child_data = Product::find($child_id);
                        if(count($variant_list) && $variant_list[$index]) {
                            $child_product_variant_data = ProductVariant::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index] ]
                            ])->first();

                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index] ],
                                ['warehouse_id', $lims_sale_data->warehouse_id ],
                            ])->first();

                             $child_product_variant_data->qty += $product_sale->qty * $qty_list[$index];
                             $child_product_variant_data->save();
                        }
                        else {
                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['warehouse_id', $lims_sale_data->warehouse_id ],
                            ])->first();
                        }

                        $child_data->qty += $product_sale->qty * $qty_list[$index];
                        $child_warehouse_data->qty += $product_sale->qty * $qty_list[$index];

                        $child_data->save();
                        $child_warehouse_data->save();
                    }
                }
                elseif(($lims_sale_data->sale_status == 1) && ($product_sale->sale_unit_id != 0)){
                    $lims_sale_unit_data = Unit::find($product_sale->sale_unit_id);
                    if ($lims_sale_unit_data->operator == '*')
                        $product_sale->qty = $product_sale->qty * $lims_sale_unit_data->operation_value;
                    else
                        $product_sale->qty = $product_sale->qty / $lims_sale_unit_data->operation_value;
                    if($product_sale->variant_id) {
                        $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_sale->variant_id)->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($lims_product_data->id, $product_sale->variant_id, $lims_sale_data->warehouse_id)->first();
                        $lims_product_variant_data->qty += $product_sale->qty;
                        $lims_product_variant_data->save();
                    }
                    elseif($product_sale->product_batch_id) {
                        $lims_product_batch_data = ProductBatch::find($product_sale->product_batch_id);
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_batch_id', $product_sale->product_batch_id],
                            ['warehouse_id', $lims_sale_data->warehouse_id]
                        ])->first();

                        $lims_product_batch_data->qty -= $product_sale->qty;
                        $lims_product_batch_data->save();
                    }
                    else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $lims_sale_data->warehouse_id)->first();
                    }

                    $lims_product_data->qty += $product_sale->qty;
                    $lims_product_warehouse_data->qty += $product_sale->qty;
                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                }
                $product_sale->is_active = 0;
                $product_sale->save();
                
            }
            $lims_payment_data = Payment::where('sale_id', $id)->get();
            foreach ($lims_payment_data as $payment) {
                if($payment->paying_method == 'Gift Card'){
                    $lims_payment_with_gift_card_data = PaymentWithGiftCard::where('payment_id', $payment->id)->first();
                    $lims_gift_card_data = GiftCard::find($lims_payment_with_gift_card_data->gift_card_id);
                    $lims_gift_card_data->expense -= $payment->amount;
                    $lims_gift_card_data->save();
                    $lims_payment_with_gift_card_data->delete();
                }
                elseif($payment->paying_method == 'Cheque'){
                    $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $payment->id)->first();
                    $lims_payment_cheque_data->delete();
                }
                elseif($payment->paying_method == 'Credit Card'){
                    $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment->id)->first();
                    $lims_payment_with_credit_card_data->delete();
                }
                elseif($payment->paying_method == 'Paypal'){
                    $lims_payment_paypal_data = PaymentWithPaypal::where('payment_id', $payment->id)->first();
                    if($lims_payment_paypal_data)
                        $lims_payment_paypal_data->delete();
                }
                elseif($payment->paying_method == 'Deposit'){
                    $lims_customer_data = Customer::find($lims_sale_data->customer_id);
                    $lims_customer_data->expense -= $payment->amount;
                    $lims_customer_data->save();
                }
                $payment->delete();
            }
            if($lims_delivery_data)
                $lims_delivery_data->delete();
            if($lims_sale_data->coupon_id) {
                $lims_coupon_data = Coupon::find($lims_sale_data->coupon_id);
                $lims_coupon_data->used -= 1;
                $lims_coupon_data->save();
            }
                $lims_sale_data->is_active = 0;
                $lims_sale_data->save();
                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $text = 'Sale Was Deleted By '.$username;
                DB::table('sale_logs')->insert(['text' => $text, 'sale_id' => $lims_sale_data->id]);
            $this->fileDelete('documents/sale/', $lims_sale_data->document);

        }
        return 'Sale deleted successfully!';
    }
    public function truncate($id){
     $url = url()->previous();
     $sale = Sale::find($id);
     if($sale){
         Sale::where('id',$id)->delete();
     }
     $message = "Successfully Truncated For Ever";
        return Redirect::to($url)->with('not_permitted', $message);

    }
    public function restore($id){
     $url = url()->previous();
     $sale = Sale::find($id);
     if($sale){
         $sale->is_active = 1;
         $sale->save();
     }
     $message = "Successfully Restored";
        return Redirect::to($url)->with('success', $message);

    }

    public function destroy($id)
    {
        $url = url()->previous();
        $lims_sale_data = Sale::find($id);
        $return_ids = Returns::where('sale_id', $id)->pluck('id')->toArray();
        if(count($return_ids)) {
            ProductReturn::whereIn('return_id', $return_ids)->delete();
            Returns::whereIn('id', $return_ids)->delete();
        }
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        $lims_delivery_data = Delivery::where('sale_id',$id)->first();
        if($lims_sale_data->sale_status == 3)
            $message = 'Draft deleted successfully';
        else
            $message = 'Sale deleted successfully';

        foreach ($lims_product_sale_data as $product_sale) {
            $lims_product_data = Product::find($product_sale->product_id);
            //adjust product quantity
            if( ($lims_sale_data->sale_status == 1) && ($lims_product_data->type == 'combo') ) {
                $product_list = explode(",", $lims_product_data->product_list);
                $variant_list = explode(",", $lims_product_data->variant_list);
                $qty_list = explode(",", $lims_product_data->qty_list);
                if($lims_product_data->variant_list)
                    $variant_list = explode(",", $lims_product_data->variant_list);
                else
                    $variant_list = [];
                foreach ($product_list as $index=>$child_id) {
                    $child_data = Product::find($child_id);
                    if(count($variant_list) && $variant_list[$index]) {
                        $child_product_variant_data = ProductVariant::where([
                            ['product_id', $child_id],
                            ['variant_id', $variant_list[$index] ]
                        ])->first();

                        $child_warehouse_data = Product_Warehouse::where([
                            ['product_id', $child_id],
                            ['variant_id', $variant_list[$index] ],
                            ['warehouse_id', $lims_sale_data->warehouse_id ],
                        ])->first();

                         $child_product_variant_data->qty += $product_sale->qty * $qty_list[$index];
                         $child_product_variant_data->save();
                    }
                    else {
                        $child_warehouse_data = Product_Warehouse::where([
                            ['product_id', $child_id],
                            ['warehouse_id', $lims_sale_data->warehouse_id ],
                        ])->first();
                    }

                    $child_data->qty += $product_sale->qty * $qty_list[$index];
                    $child_warehouse_data->qty += $product_sale->qty * $qty_list[$index];

                    $child_data->save();
                    $child_warehouse_data->save();
                }
            }
            elseif(($lims_sale_data->sale_status == 1) && ($product_sale->sale_unit_id != 0)) {
                $lims_sale_unit_data = Unit::find($product_sale->sale_unit_id);
                if ($lims_sale_unit_data->operator == '*')
                    $product_sale->qty = $product_sale->qty * $lims_sale_unit_data->operation_value;
                else
                    $product_sale->qty = $product_sale->qty / $lims_sale_unit_data->operation_value;
                if($product_sale->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_sale->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($lims_product_data->id, $product_sale->variant_id, $lims_sale_data->warehouse_id)->first();
                    $lims_product_variant_data->qty += $product_sale->qty;
                    $lims_product_variant_data->save();
                }
                elseif($product_sale->product_batch_id) {
                    $lims_product_batch_data = ProductBatch::find($product_sale->product_batch_id);
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_batch_id', $product_sale->product_batch_id],
                        ['warehouse_id', $lims_sale_data->warehouse_id]
                    ])->first();

                    $lims_product_batch_data->qty -= $product_sale->qty;
                    $lims_product_batch_data->save();
                }
                else {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $lims_sale_data->warehouse_id)->first();
                }

                $lims_product_data->qty += $product_sale->qty;
                $lims_product_warehouse_data->qty += $product_sale->qty;
                $lims_product_data->save();
                $lims_product_warehouse_data->save();
            }
            if($product_sale->imei_number) {
                if($lims_product_warehouse_data->imei_number)
                    $lims_product_warehouse_data->imei_number .= ',' . $product_sale->imei_number;
                else
                    $lims_product_warehouse_data->imei_number = $product_sale->imei_number;
                $lims_product_warehouse_data->save();
            }
                $product_sale->is_active = 0;
                $product_sale->save();
        }

        $lims_payment_data = Payment::where('sale_id', $id)->get();
        foreach ($lims_payment_data as $payment) {
            if($payment->paying_method == 'Gift Card'){
                $lims_payment_with_gift_card_data = PaymentWithGiftCard::where('payment_id', $payment->id)->first();
                $lims_gift_card_data = GiftCard::find($lims_payment_with_gift_card_data->gift_card_id);
                $lims_gift_card_data->expense -= $payment->amount;
                $lims_gift_card_data->save();
                $lims_payment_with_gift_card_data->delete();
            }
            elseif($payment->paying_method == 'Cheque'){
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $payment->id)->first();
                if($lims_payment_cheque_data)
                    $lims_payment_cheque_data->delete();
            }
            elseif($payment->paying_method == 'Credit Card'){
                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment->id)->first();
                if($lims_payment_with_credit_card_data)
                    $lims_payment_with_credit_card_data->delete();
            }
            elseif($payment->paying_method == 'Paypal'){
                $lims_payment_paypal_data = PaymentWithPaypal::where('payment_id', $payment->id)->first();
                if($lims_payment_paypal_data)
                    $lims_payment_paypal_data->delete();
            }
            elseif($payment->paying_method == 'Deposit'){
                $lims_customer_data = Customer::find($lims_sale_data->customer_id);
                $lims_customer_data->expense -= $payment->amount;
                $lims_customer_data->save();
            }
            $payment->delete();
        }
        if($lims_delivery_data)
            $lims_delivery_data->delete();
        if($lims_sale_data->coupon_id) {
            $lims_coupon_data = Coupon::find($lims_sale_data->coupon_id);
            $lims_coupon_data->used -= 1;
            $lims_coupon_data->save();
        }

                $lims_sale_data->is_active = 0;
                $lims_sale_data->save();
                $user = User::find(auth()->user()->id);
                $username = $user->name;
                $text = 'Sale Was Deleted By '.$username;
                DB::table('sale_logs')->insert(['text' => $text, 'sale_id' => $lims_sale_data->id]);
                
        $this->fileDelete('documents/sale/', $lims_sale_data->document);

        return Redirect::to($url)->with('not_permitted', $message);
    }
}
