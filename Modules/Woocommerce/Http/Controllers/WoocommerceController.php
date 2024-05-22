<?php

namespace Modules\Woocommerce\Http\Controllers;

use DB;
use Auth;
use Session;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Currency;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Product_Sale;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Product_Warehouse;
use App\Models\Account;
use App\Models\Payment;
use Automattic\WooCommerce\Client;
use Carbon\Carbon;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Models\WoocommerceSyncLog;
use Modules\Woocommerce\Exceptions\WooCommerceError;

class WoocommerceController extends Controller
{
    private $woocommerce;
    private  $created_data;
    private  $updated_data;

    public function index()
    {
        $notify = [];

        $notSyncedCatcount = Category::where('is_active', 1)->whereNull('is_sync_disable')
                            ->whereNull('woocommerce_category_id')
                            ->count();

        if (!empty($notSyncedCatcount)) {
            $notify['not_synced_cat'] = $notSyncedCatcount . ' ' . ($notSyncedCatcount == 1 ? trans('file.Category not synced') : trans('file.Categories not synced'));
        }

        $updatedCatCount = Category::where('is_active', 1)->whereNull('is_sync_disable')
                            ->whereNotNull('woocommerce_category_id')
                            ->count();

        if (!empty($updatedCatCount)) {
            $notify['updated_cat'] = $updatedCatCount . ' ' . ($updatedCatCount == 1 ? trans('file.Category can be updated') : trans('file.Categories can be updated'));
        }

        $not_synced_product_count = Product::leftJoin('categories', 'categories.id', '=', 'products.category_id')
                                    ->whereNull('categories.is_sync_disable')->where('products.type', 'standard')
                                    ->where('products.is_active', 1)->whereNull('products.is_sync_disable')
                                    ->whereNull('products.woocommerce_product_id')
                                    ->count();

        if (!empty($not_synced_product_count)) {
            $notify['not_synced_product'] = $not_synced_product_count . ' ' . ($not_synced_product_count == 1 ? trans('file.Product not synced') : trans('file.Products not synced'));
        }

        $updated_product_count = Product::leftJoin('categories', 'categories.id', '=', 'products.category_id')
                                ->whereNull('categories.is_sync_disable')->where('products.type', 'standard')
                                ->where('products.is_active', 1)->whereNull('products.is_sync_disable')
                                ->whereNotNull('products.woocommerce_product_id')
                                ->count();

        if (!empty($updated_product_count)) {
            $notify['not_updated_product'] = $updated_product_count . ' ' . ($updated_product_count == 1 ? trans('file.Product can be updated') : trans('file.Products can be updated'));
        }

        $tax_rates = Tax::where('is_active', 1)->get();

        $woocommerce_tax_rates = $this->getWoocommerceTaxRates();

        return view('woocommerce::index', compact(
            'notify',
            'tax_rates',
            'woocommerce_tax_rates'
        ));
    }

    public function getWoocommerceTaxRates()
    {
        $woocommerce_setting = WoocommerceSetting::latest()->first();
        $this->woocommerce = $this->woocommerceApi($woocommerce_setting);
        if ($this->woocommerce != null) {
            $woocommerce_tax_info = $this->getAllResponse('taxes');
        }

        $woocommerce_tax_rates = ['' => '--Select--'];
        if (!empty($woocommerce_tax_info)) {
            foreach ($woocommerce_tax_info as $w) {
                $woocommerce_tax_rates[$w->id] = $w->name;
            }
        }
        return $woocommerce_tax_rates;
    }

    public function syncLog()
    {
        $woocommerceSyncLog = WoocommerceSyncLog::leftjoin('users', 'users.id', '=', 'woocommerce_sync_logs.synced_by')
        ->select([
            'woocommerce_sync_logs.created_at',
            'sync_type',
            'operation',
            'records',
            'name'
        ])->get();
        return view('woocommerce::sync_log', compact('woocommerceSyncLog'));
    }

    public function settings()
    {
        $customer_group = ['' => '--Select--'];
        $customer_group_info = CustomerGroup::where('is_active', 1)->get();
        if (!empty($customer_group_info)) {
            foreach ($customer_group_info as $c) {
                $customer_group[$c->id] = $c->name;
            }
        }

        $warehouse = ['' => '--Select--'];
        $warehouse_info = Warehouse::where('is_active', 1)->get();
        if (!empty($warehouse_info)) {
            foreach ($warehouse_info as $wh) {
                $warehouse[$wh->id] = $wh->name;
            }
        }

        $biller = ['' => '--Select--'];
        $biller_info = Biller::where('is_active', 1)->get();
        if (!empty($biller_info)) {
            foreach ($biller_info as $b) {
                $biller[$b->id] = $b->name;
            }
        }

        $woocommerce_setting = WoocommerceSetting::latest()->first();

        return view('woocommerce::settings', compact('woocommerce_setting', 'customer_group', 'warehouse', 'biller'));
    }

    public function store(Request $request)
    {
        $woocommerce_setting = WoocommerceSetting::latest()->first();

        if (is_null($woocommerce_setting) && empty($woocommerce_setting)) {
            $woocommerce_setting = new WoocommerceSetting();
        }

        if ($request->api_settings) {
            $woocommerce_setting->woocomerce_app_url = $request->woocomerce_app_url;
            $woocommerce_setting->woocomerce_consumer_key = $request->woocomerce_consumer_key;
            $woocommerce_setting->woocomerce_consumer_secret = $request->woocomerce_consumer_secret;
        }
        else if ($request->product_sync_settings) {
            $woocommerce_setting->default_tax_class = $request->default_tax_class;
            $woocommerce_setting->product_tax_type = $request->product_tax_type;
            $woocommerce_setting->manage_stock = $request->manage_stock;
            $woocommerce_setting->stock_status = $request->stock_status;
            $woocommerce_setting->product_status = $request->product_status;
        }
        else if ($request->order_sync_settings) {
            $woocommerce_setting->customer_group_id = $request->customer_group_id;
            $woocommerce_setting->warehouse_id = $request->warehouse_id;
            $woocommerce_setting->biller_id = $request->biller_id;
            $woocommerce_setting->order_status_pending = $request->sell_status[0];
            $woocommerce_setting->order_status_processing = $request->sell_status[1];
            $woocommerce_setting->order_status_on_hold = $request->sell_status[2];
            $woocommerce_setting->order_status_completed = $request->sell_status[3];
            $woocommerce_setting->order_status_draft = $request->sell_status[4];
        }

        else if ($request->webhook_settings) {
            $woocommerce_setting->webhook_secret_order_created = $request->webhook_secret_order_created;
            $woocommerce_setting->webhook_secret_order_updated = $request->webhook_secret_order_updated;
            $woocommerce_setting->webhook_secret_order_deleted = $request->webhook_secret_order_deleted;
            $woocommerce_setting->webhook_secret_order_restored = $request->webhook_secret_order_restored;
        }

        $woocommerce_setting->save();
        return redirect()->back();
    }

    public function woocommerceApi($woocommerce_setting)
    {
        if (!is_null($woocommerce_setting) && !empty($woocommerce_setting) &&  $woocommerce_setting->woocomerce_app_url && $woocommerce_setting->woocomerce_consumer_key && $woocommerce_setting->woocomerce_consumer_secret) {
            return new Client(
                $woocommerce_setting->woocomerce_app_url,
                $woocommerce_setting->woocomerce_consumer_key,
                $woocommerce_setting->woocomerce_consumer_secret,
                [
                    'wp_api' => true,
                    'version' => 'wc/v3',
                ]
            );
        }
        else {
            Session::flash('message', trans('file.Please connect with WooCommerce'));
            Session::flash('type', 'danger');
        }
    }

    public function syncCategories()
    {
        if(!env('USER_VERIFIED')) {
            $output = [
                'success' => 0,
                'msg' => 'This feature is disable for demo!'
            ];
            return $output;
        }
        $woocommerce_setting = WoocommerceSetting::latest()->first();
        $this->woocommerce = $this->woocommerceApi($woocommerce_setting);
        if ($this->woocommerce != null) {
            try {
                DB::beginTransaction();

                $categories = Category::where('is_active', 1)->whereNull('is_sync_disable')
                                ->get();

                $category_data = [];
                $new_categories = [];
                $this->created_data = [];
                $this->updated_data = [];

                foreach ($categories as $category) {
                    if (empty($category->woocommerce_category_id)) {
                        $category_data['create'][] = [
                            'name' => $category->name
                        ];
                        $new_categories[] = $category;
                        $this->created_data[] = $category->name;
                    } else {
                        $category_data['update'][] = [
                            'id' => $category->woocommerce_category_id,
                            'name' => $category->name
                        ];
                        $this->updated_data[] = $category->name;
                    }
                }

                if (!empty($category_data['create'])) {
                    $this->syncCat($category_data['create'], 'create', $new_categories);
                }
                if (!empty($category_data['update'])) {
                    $this->syncCat($category_data['update'], 'update', $new_categories);
                }

                //assign parent category for child categories
                $child_categories = Category::where('is_active', 1)->whereNull('is_sync_disable')
                                    ->where('parent_id', '!=', NULL)
                                    ->get();

                $this->assignParentForChildCategories($child_categories);

                //Create log
                if (!empty( $this->created_data)) {
                    $this->createWoocommerceSyncLog('categories', 'created',  $this->created_data);
                }
                if (!empty( $this->updated_data)) {
                    $this->createWoocommerceSyncLog('categories', 'updated',  $this->updated_data);
                }

                DB::commit();

                $output = [
                    'success' => 1,
                    'msg' => trans('file.Synced category successfully')
                ];
            } catch (Exception $e) {
                DB::rollBack();

                if (get_class($e) == 'Modules\Woocommerce\Exceptions\WooCommerceError') {
                    $output = [
                        'success' => 0,
                        'msg' => $e->getMessage(),
                    ];
                } else {
                    $output = [
                        'success' => 0,
                        'msg' => trans('file.Something went wrong'.$e->getMessage())
                    ];
                }
            }
        }
        else {
            $output = [
                'success' => 0,
                'msg' => trans('file.Please connect with WooCommerce')
            ];
        }
        return $output;
    }

    public function syncCat($data, $type, $new_categories = [])
    {
        $count = 0;
        foreach (array_chunk($data, 99) as $chunked_array) {
            $sync_data = [];
            $sync_data[$type] = $chunked_array;

            //Batch update categories
            $response = $this->woocommerce->post('products/categories/batch', $sync_data);

            //update woocommerce_category_id after creating categories in woocommerce
            if (!empty($response->create)) {
                foreach ($response->create as $key => $value) {
                    $new_category = $new_categories[$count];
                    if ($value->id != 0) {
                        $new_category->woocommerce_category_id = $value->id;
                    } else {
                        if (!empty($value->error->data->resource_id)) {
                            $new_category->woocommerce_category_id = $value->error->data->resource_id;
                        }
                    }
                    $new_category->save();
                    $count++;
                }
            }
        }
    }

    public function assignParentForChildCategories($child_categories)
    {
        $category_id_woocommerce_id = Category::where('is_active', 1)->whereNull('is_sync_disable')
            ->pluck('woocommerce_category_id', 'id')
            ->toArray();

        $category_data = [];
        $new_categories = [];
        foreach ($child_categories as $category) {
            if (empty($category_id_woocommerce_id[$category->parent_id])) {
                continue;
            }
            $category_data['update'][] = [
                'id' => $category->woocommerce_category_id,
                'parent' => $category_id_woocommerce_id[$category->parent_id]
            ];
        }

        if (!empty($category_data['update'])) {
            $this->syncCat($category_data['update'], 'update', $new_categories);
        }
    }

    public function resetSyncedCategory()
    {
        try {
            Category::where('is_active', 1)->whereNull('is_sync_disable')->update(['woocommerce_category_id' => null]);
            $this->createWoocommerceSyncLog('categories', 'reset', null);
            $output = ['success' => 1, 'msg' => trans('file.Reset synced category successfully')];
        }
        catch (Exception $e) {
            $output = ['success' => 0, 'msg' => trans('file.Something went wrong')];
        }
        return $output;
    }

    public function syncProducts()
    {
        $woocommerce_setting = WoocommerceSetting::latest()->first();

        $this->woocommerce = $this->woocommerceApi($woocommerce_setting);

        if ($this->woocommerce != null) {
            try {
                DB::beginTransaction();

                $products = Product::leftjoin('taxes', 'taxes.id', '=', 'products.tax_id')
                        ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                        ->whereNull('categories.is_sync_disable')
                        ->where('products.type', 'standard')->where('products.is_active', 1)
                        ->whereNull('products.is_sync_disable')
                        ->select(['products.*', 'rate', 'woocommerce_category_id'])
                        ->get();

                $this->created_data = [];
                $this->updated_data = [];

                $product_data = [];
                $new_products = [];

                foreach ($products as $product) {
                    $prod_data_array = [];

                    $prod_data_array['name'] = $product->name;

                    if ($product->is_variant == 1) {
                        $prod_data_array['type'] = 'variable';
                    }
                    else {
                        $prod_data_array['type'] = 'simple';
                        if ($woocommerce_setting->stock_status != null) {
                            $prod_data_array['stock_status'] = $woocommerce_setting->stock_status;
                        }
                    }

                    $prod_data_array['sku'] = $product->code;

                    $prod_data_array['description'] = $product->product_details;

                    $prod_data_array['regular_price'] = $this->priceForWoocoomerce($product->price, $product->rate, $woocommerce_setting->product_tax_type, $product->tax_method);

                    $prod_data_array['categories'] = [
                        [
                            'id' => $product->woocommerce_category_id
                        ]
                    ];

                    $prod_data_array['stock_quantity'] = $product->qty;

                    $prod_data_array['tax_class'] = ($woocommerce_setting->default_tax_class != null) ?
                    $woocommerce_setting->default_tax_class : 'standard';

                    if ($woocommerce_setting->manage_stock != null) {
                        $prod_data_array['manage_stock'] = $woocommerce_setting->manage_stock;
                    }
                    ///for update////
                    if ($woocommerce_setting->product_status != null) {
                        $prod_data_array['status'] = $woocommerce_setting->product_status;
                    }
                    ///for update////

                    if (!empty($product->image) && file_exists('public/images/product/'.$product->image)) {
                        $prod_data_array['images'] = !empty($product->woocommerce_media_id) ? [['id' => $product->woocommerce_media_id]] : [['src' => url('images/product', $product->image)]];
                    }

                    if (empty($product->woocommerce_product_id)) {
                         ///for client////
                        // if ($woocommerce_setting->product_status != null) {
                        //     $prod_data_array['status'] = $woocommerce_setting->product_status;
                        // }
                        ///for client////
                        $product_data['create'][] = $prod_data_array;
                        $new_products[] = $product;
                        $this->created_data[] = $product->name;
                    }
                    else {
                        $prod_data_array['id'] = $product->woocommerce_product_id;
                        $product_data['update'][] = $prod_data_array;
                        $this->updated_data[] = $product->name;
                    }
                }

                if (!empty($product_data['create'])) {
                    $this->syncProd($product_data['create'], 'create', $new_products);
                }
                if (!empty($product_data['update'])) {
                    $this->syncProd($product_data['update'], 'update', $new_products);
                }

                //Create log
                if (!empty( $this->created_data)) {
                    $this->createWoocommerceSyncLog('products', 'created',  $this->created_data);
                }
                if (!empty( $this->updated_data)) {
                    $this->createWoocommerceSyncLog('products', 'updated',  $this->updated_data);
                }

                DB::commit();

                $output = [
                    'success' => 1,
                    'msg' => trans('file.Synced product successfully')
                ];
            } catch (Exception $e) {
                DB::rollBack();

                if (get_class($e) == 'Modules\Woocommerce\Exceptions\WooCommerceError') {
                    $output = [
                        'success' => 0,
                        'msg' => $e->getMessage(),
                    ];
                } else {
                    $output = [
                        'success' => 0,
                        'msg' => trans('file.Something went wrong' . $e->getMessage()) 
                    ];
                }
            }
        }
        else {
            $output = [
                'success' => 0,
                'msg' => trans('file.Please connect with WooCommerce')
            ];
        }
        return $output;
    }

    public function syncProd($data, $type, $new_products = [])
    {
        $count = 0;
        foreach (array_chunk($data, 99) as $chunked_array) {
            $sync_data = [];
            $sync_data[$type] = $chunked_array;

            //Batch update products
            $response = $this->woocommerce->post('products/batch', $sync_data);

            //update woocommerce_product_id
            if (!empty($response->create)) {
                foreach ($response->create as $key => $value) {
                    $new_product = $new_products[$count];
                    if ($value->id != 0) {
                        $new_product->woocommerce_product_id = $value->id;
                        $new_product->woocommerce_media_id = !empty($value->images[0]->id) ? $value->images[0]->id : null;
                    } else {
                        if (!empty($value->error->data->resource_id)) {
                            $new_product->woocommerce_product_id = $value->error->data->resource_id;
                        }
                    }
                    $new_product->save();
                    $count++;
                }
            }
        }
    }

    public function priceForWoocoomerce($product_price, $tax_rate, $product_tax_type, $tax_method)
    {
        // SalePro tax_method Exclusive = 1
        // SalePro tax_method Inclusive = 2
        $woocommerce_price = $product_price;
        if ($tax_rate != null) {
            if($product_tax_type=='exc'){
                if($tax_method==1){
                    $woocommerce_price = $product_price;
                }
                else {
                    $woocommerce_price = (100 * $product_price) / ($tax_rate + 100);
                }
            }
            else {
                if($tax_method==1){
                    $woocommerce_price = $product_price + $product_price * ($tax_rate /100);
                }
                else {
                    $woocommerce_price = $product_price;
                }
            }
        }
        return $woocommerce_price;
    }

    public function createWoocommerceSyncLog($type, $operation = null, $records = [])
    {
        WoocommerceSyncLog::create([
            'sync_type' => $type,
            'operation' => $operation,
            'records' => !empty($records) ? json_encode($records) : null,
            'synced_by' => Auth::id()
        ]);
    }

    public function resetSyncedProduct()
    {
        try {
            Product::where('type', 'standard')->where('is_active', 1)
                    ->update(['woocommerce_product_id' => null, 'woocommerce_media_id' => null]);
            $this->createWoocommerceSyncLog('products', 'reset', null);
            $output = ['success' => 1, 'msg' => trans('file.Reset synced product successfully')];
        }
        catch (Exception $e) {
            $output = ['success' => 0, 'msg' => trans('file.Something went wrong')];
        }
        return $output;
        //if need variable need to Update variations table and Update variation templates
    }

    public function mapTaxRates(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->except('_token');
            foreach ($input['taxes'] as $key => $value) {
                $value = !empty($value) ? $value : null;
                Tax::where('id', $key)
                        ->update(['woocommerce_tax_id' => $value]);
            }
            DB::commit();
            $output = ['success' => 1, 'msg' => trans('file.Tax maped successfully')];
        } catch (Exception $e) {
            DB::rollBack();
            $output = ['success' => 0, 'msg' => trans('file.Something went wrong')];
        }
        return $output;
    }

    public function syncOrders()
    {
        $woocommerce_setting = WoocommerceSetting::latest()->first();
        $this->woocommerce = $this->woocommerceApi($woocommerce_setting);
        if ($this->woocommerce != null) {
            if ($woocommerce_setting->customer_group_id && $woocommerce_setting->warehouse_id && $woocommerce_setting->biller_id && $woocommerce_setting->order_status_pending && $woocommerce_setting->order_status_on_hold && $woocommerce_setting->order_status_draft && $woocommerce_setting->order_status_processing && $woocommerce_setting->order_status_completed) {
                try {
                    DB::beginTransaction();
                    $currency_id = Currency::where('exchange_rate', 1)->first()->id;
                    $orders = $this->getAllResponse('orders');
                    $taxes = Tax::whereNotNull('woocommerce_tax_id')->pluck('rate','woocommerce_tax_id')->toArray();

                    $woocommerce_sells = Sale::whereNotNull('woocommerce_order_id')->get();

                    $products_data = Product::whereNotNull('woocommerce_product_id')->get();

                    $products = $products_data->mapWithKeys(function ($item) {
                            return [$item->woocommerce_product_id => [$item->id, $item->sale_unit_id, $item->qty]];
                        })->toArray();

		            $products_in_warehouse = Product_Warehouse::where('warehouse_id', $woocommerce_setting->warehouse_id)->pluck('product_id')->toArray();

                    $sale_unit = Unit::where('is_active', 1)->get()->mapWithKeys(function ($item) {
                        return [$item->id => [$item->operator, $item->operation_value]];
                    });

                    $this->created_data = [];
                    foreach ($orders as $order) {

                        if ($order->status == 'cancelled' || $order->status == 'refunded' || $order->status == 'failed') {
                            continue;
                        }

                        //Search if order already exists
                        $order_exist = $woocommerce_sells->filter(function ($item) use ($order) {
                            return $item->woocommerce_order_id == $order->id;
                        })->first();

                        if (empty($order_exist)) {
                            //create customer
                            $customer_data =[];
                            $customer_data['customer_group_id'] = $woocommerce_setting->customer_group_id;
                            $customer_data['name'] = $order->billing->first_name . ' '. $order->billing->last_name;
                            $customer_data['company_name'] = $order->billing->company;
                            $customer_data['email'] = $order->billing->email;
                            $customer_data['phone_number'] = $order->billing->phone ? $order->billing->phone : '000000';
                            $customer_data['address'] = $order->billing->address_1 . ' '. $order->billing->address_2;
                            $customer_data['city'] = $order->billing->city;
                            $customer_data['state'] = $order->billing->state;
                            $customer_data['postal_code'] = $order->billing->postcode;
                            $customer_data['country'] = $order->billing->country;
                            $customer_data['is_active'] = 1;

                            $customer = Customer::updateOrCreate(['name'=>$customer_data['name'], 'phone_number'=>$customer_data['phone_number']], $customer_data);
                            //create sale
                            $item = 0;
                            $total_qty = 0;
                            $total_price = 0;
                            foreach ($order->line_items as $line_item) {
                                if (!array_key_exists($line_item->product_id,$products)) {
                                    $output = [
				                        'success' => 0,
				                        'msg' => 'This order #'. $order->id.' contains product '.$line_item->name . ' with id '.$line_item->product_id.' that is not available within the POS system'
			                        ];
			                        return $output;
                                }
                            	if (!in_array($products[$line_item->product_id][0], $products_in_warehouse)) {
                            	    $output = [
				                        'success' => 0,
				                        'msg' => 'This order #'. $order->id.' contains product '.$line_item->name . ' that has no stock in selected warehouse'
			                        ];
			                        return $output;
				                }
                                $item++;
                                $total_qty += $line_item->quantity;
                                $total_price += $line_item->subtotal;
                            }

                            $sale_data =[];
                            $sale_data['reference_no'] = 'woo-' . date("Ymd") . '-'. date("his");
                            $sale_data['user_id'] = Auth::id();
                            $sale_data['customer_id'] = $customer->id;
                            $sale_data['warehouse_id'] = $woocommerce_setting->warehouse_id;
                            $sale_data['biller_id'] = $woocommerce_setting->biller_id;
                            $sale_data['item'] = $item;
                            $sale_data['total_qty'] = $total_qty;
                            $sale_data['total_discount'] = 0;
                            $sale_data['order_discount_type'] = 'Flat';
                            $sale_data['order_discount_value'] = $order->discount_total;
                            $sale_data['order_discount'] = $order->discount_total;
                            $sale_data['total_tax'] = $order->cart_tax;
                            $sale_data['total_price'] = $total_price;
                            $sale_data['grand_total'] = $order->total;
                            $sale_data['currency_id'] = $currency_id;
                            $sale_data['exchange_rate'] = 1;
                            $sale_data['shipping_cost'] = $order->shipping_total + $order->shipping_tax;

                            if ($order->status == 'pending') {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_pending;
                            }
                            else if ($order->status == 'on-hold') {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_on_hold;
                            }
                            else if ($order->status == 'draft') {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_draft;
                            }
                            else if ($order->status == 'processing') {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_processing;
                            }
                            else if ($order->status == 'completed') {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_completed;
                            }
                            else {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_pending;
                            }

                            if ($order->payment_method == 'cod') {
                                $sale_data['payment_status'] = 2;
                            }
                            else {
                                $sale_data['payment_status'] = 4;
                            }

                            $sale_data['woocommerce_order_id'] = $order->id;

                            $sale = Sale::create($sale_data);

                            //create product_sales
                            foreach ($order->line_items as $line_item) {
                                $product_sales_data =[];
                                $product_sales_data['sale_id'] =  $sale->id;
                                $product_sales_data['product_id'] = $products[$line_item->product_id][0];
                                $product_sales_data['qty'] = $line_item->quantity;
                                $product_sales_data['sale_unit_id'] = $products[$line_item->product_id][1];
                                $product_sales_data['net_unit_price'] = ($line_item->subtotal)/($line_item->quantity);
                                $product_sales_data['discount'] = 0;
                                if (!empty($line_item->taxes)) {
                                    if (array_key_exists($line_item->taxes[0]->id,$taxes)) {
                                        $product_sales_data['tax_rate'] = $taxes[$line_item->taxes[0]->id];
                                        $product_sales_data['tax'] = $line_item->taxes[0]->total;
                                    } else {
                                        $output = [
                                            'success' => 0,
                                            'msg' => 'This order #'. $order->id.' contains product '.$line_item->name . ' that has a unmapped tax'
                                        ];
                                        return $output;
                                    }
                                } else {
                                    $product_sales_data['tax_rate'] = 0;
                                    $product_sales_data['tax'] = 0;
                                }
                                $product_sales_data['total'] = $line_item->subtotal;
                                Product_Sale::create($product_sales_data);
                                if($sale_data['sale_status'] == 1) {
                                    if($sale_unit[$products[$line_item->product_id][1]][0] == '*')
                                        $quantity = $line_item->quantity * $sale_unit[$products[$line_item->product_id][1]][1];
                                    elseif($sale_unit[$products[$line_item->product_id][1]][0] == '/')
                                        $quantity = $line_item->quantity / $sale_unit[$products[$line_item->product_id][1]][1];

                                    //deduct quantity from product
                                    $qty = $products[$line_item->product_id][2] - $quantity;
                                    $products_data->find($products[$line_item->product_id][0])->update(['qty'=> $qty]);

                                    $product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($products[$line_item->product_id][0], $sale_data['warehouse_id'])->first();
                                    //deduct quantity from warehouse
                                    $product_warehouse_data->qty -= $quantity;
                                    $product_warehouse_data->save();
                                }
                            }

                            //create payements
                            if($sale_data['payment_status'] == 4) {
                                $payments_data =[];
                                $payments_data['payment_reference'] = 'spr-'.date("Ymd").'-'.date("his");
                                $payments_data['user_id'] = Auth::id();
                                $payments_data['sale_id'] = $sale->id;
                                $payments_data['account_id'] = Account::where('is_default', true)->first()->id;
                                $payments_data['amount'] = $order->total;
                                $payments_data['change'] = 0;
                                $payments_data['paying_method'] = 'Cash';
                                Payment::create($payments_data);
                            }

                            $this->created_data[] = $sale_data['reference_no'];
                        }
                    }

                    //Create log
                    if (!empty($this->created_data)) {
                        $this->createWoocommerceSyncLog('orders', 'created',  $this->created_data);
                    }

                    DB::commit();

                    $output = ['success' => 1,
                                    'msg' => trans('file.Synced order successfully')
                                ];
                } catch (Exception $e) {
                    DB::rollBack();

                    if (get_class($e) == 'Modules\Woocommerce\Exceptions\WooCommerceError') {
                        $output = ['success' => 0,
                                    'msg' => $e->getMessage(),
                                ];
                    }
                    else {
                        $output = ['success' => 0,
                                    'msg' => $e->getMessage(),
                                ];
                    }
                }
            } else {
                $output = [
                    'success' => 0,
                    'msg' => trans('file.Please select Default Customer Group/ Default Warehouse/ Default Biller/ Equivalent POS Sell Status in Order Sync Settings')
                ];
            }
        }
        else {
            $output = [
                'success' => 0,
                'msg' => trans('file.Please connect with WooCommerce')
            ];
        }
        return $output;
    }

    public function getAllResponse($get_what, $params = [])
    {
        $page = 1;
        $list = [];
        $all_list = [];
        $params['per_page'] = 100;

        do {
            $params['page'] = $page;
            try {
                $list = $this->woocommerce->get($get_what, $params);
            } catch (Exception $e) {
                return [];
            }
            $all_list = array_merge($all_list, $list);
            $page++;
        } while (count($list) > 0);

        return $all_list;
    }

}
