
        
        <!DOCTYPE html>
<html>
  <head>
     @php
        $totalOrders = 0;
    @endphp
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" type="image/png" href="{{url('logo', $general_setting->site_logo)}}" />
    <title>{{$general_setting->site_title}}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<style type="text/css">
    @media print {
        body {
            margin: 0;
            padding: 0;
        }

        .invoice {
    page-break-before: always;
    page-break-inside: avoid;
    width: 148mm;
    height: 210mm;
    margin: auto;
    position: relative;
    top: 0;
    transform: translateY(4%);
    background-color: white;
}

        /* ... باقي الأماط ... */

        @page {
            margin: 0.5cm 0.5cm 0.5cm;
        }

        @page:first {
            margin-top: 0.5cm;
        }

        /*tbody::after {
            content: '';
            display: block;
            page-break-after: always;
            page-break-inside: avoid;
            page-break-before: avoid;
        }*/
    }

    .btn {
        padding: 7px 10px;
        text-decoration: none;
        border: none;
        display: block;
        text-align: center;
        margin: 7px;
        cursor:pointer;
    }

    .btn-info {
        background-color: #999;
        color: #FFF;
    }

    .btn-primary {
        background-color: #6449e7;
        color: #FFF;
        width: 100%;
    }

    td,
    th,
    tr,
    table {
        border-collapse: collapse;
    }

    tr {
        border-bottom: 3px solid #000;
    }

    td,th {
        padding: 7px 0;
        width: 9.6%;
    }

    table {
        width: 100%;
    }

    tfoot tr th:first-child {
        text-align: left;
    }

    .centered {
        text-align: center;
        align-content: center;
    }

    small {
        font-size: 13px;
    }

    @media print {
        * {
            font-size: 12px;
            line-height: 20px;
        }

        td,th {
            padding: 5px 0;
        }

        .hidden-print {
            display: none !important;
        }
    }
</style>

  </head>
<body>
  @if(preg_match('~[0-9]~', url()->previous()))
        @php $url = '../../pos'; @endphp
    @else
        @php $url = url()->previous(); @endphp
    @endif
    <div class="hidden-print">
        <table>
            <tr>
                <td><a href="{{$url}}" class="btn btn-info"><i class="fa fa-arrow-left"></i> {{trans('file.Back')}}</a> </td>
                <td><button onclick="window.print();" class="btn btn-primary"><i class="dripicons-print"></i> {{trans('file.Print')}}</button></td>
                <td><button onclick="topdf();" class="btn btn-success"><i class="dripicons-print"></i> {{trans('file.Save PDF')}}</button></td>
            </tr>
        </table>
        <br>
    </div>
    <div id="content">
  <?php
          $totalgrandprice = 0;
   ?>
<?php
$finaltotal = 0;
        foreach ($ids as $id) {
            
        $lims_sale_data = \App\Models\Sale::find($id);
        $lims_product_sale_data = \App\Models\Product_Sale::where('sale_id', $id)->get();
        if(cache()->has('biller_list'))
        {
            $lims_biller_data = cache()->get('biller_list')->find($lims_sale_data->biller_id);
        }
        else{
            $lims_biller_data =\App\Models\Biller::find($lims_sale_data->biller_id);
        }
        if(cache()->has('warehouse_list'))
        {
            $lims_warehouse_data = cache()->get('warehouse_list')->find($lims_sale_data->warehouse_id);
        }
        else{
            $lims_warehouse_data = \App\Models\Warehouse::find($lims_sale_data->warehouse_id);
        }

        if(cache()->has('customer_list'))
        {
            $lims_customer_data = cache()->get('customer_list')->find($lims_sale_data->customer_id);
        }
        else{
            $lims_customer_data = \App\Models\Customer::find($lims_sale_data->customer_id);
        }

        $lims_payment_data = \App\Models\Payment::where('sale_id', $id)->get();
        if(cache()->has('pos_setting'))
        {
            $lims_pos_setting_data = cache()->get('pos_setting');
        }
        else{
            $lims_pos_setting_data = \App\Models\PosSetting::select('invoice_option')->latest()->first();
        }

        $supportedIdentifiers = [
            'al', 'fr_BE', 'pt_BR', 'bg', 'cs', 'dk', 'nl', 'et', 'ka', 'de', 'fr', 'hu', 'id', 'it', 'lt', 'lv',
            'ms', 'fa', 'pl', 'ro', 'sk', 'es', 'ru', 'sv', 'tr', 'tk', 'ua', 'yo'
        ]; //ar, az, ku, mk - not supported

        $defaultLocale = \App::getLocale();
        $numberToWords = new \NumberToWords\NumberToWords();

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
        $paying_methods = \App\Models\Payment::where('sale_id', $id)->pluck('paying_method')->toArray();
        $paid_by_info = '';
        foreach ($paying_methods as $key => $paying_method) {
            if($key)
                $paid_by_info .= ', '.$paying_method;
            else
                $paid_by_info = $paying_method;
        }
        $sale_custom_fields = \App\Models\CustomField::where([
                                ['belongs_to', 'sale'],
                                ['is_invoice', true]
                            ])->pluck('name');
        $customer_custom_fields = \App\Models\CustomField::where([
                                ['belongs_to', 'customer'],
                                ['is_invoice', true]
                            ])->pluck('name');
        $product_custom_fields = \App\Models\CustomField::where([
                                ['belongs_to', 'product'],
                                ['is_invoice', true]
                            ])->pluck('name');
                             $finaltotal = $finaltotal + $lims_sale_data->grand_total; 
        if($lims_sale_data->address_id > 0){
          $address =  \App\Models\CustomerAddress::find($lims_sale_data->address_id);
          $lims_customer_data->address = $address->address_text;
          $lims_customer_data->city = $address->city_text;
          $lims_customer_data->state = $address->state_text;
        }
        if($lims_pos_setting_data->invoice_option == 'A4') {
          ?>
@foreach($lims_product_sale_data as $key => $product_sale_data)
            <?php
                // الكواد الحالية للحول على الب
                $totalOrders += 1; // زيادة عد الطلبات
            ?>
            <!-- الأكواد الحالية لعرض الطلبات -->
        @endforeach
                <div class="invoice">

<div style="max-width:<?php 
        if($lims_pos_setting_data->invoice_option == 'A4') { echo "1000px;";}else{ echo "400px;";} ?>margin:0 auto">
  
          <table style="max-width: 1000px;
    margin: 0 auto; border-collapse: collapse;">

                <tr>
    <td colspan="6" style="border:3px solid black; text-align: center; line-height: 0px; width: 86%; height: 30px;">
        <br>
        <?php
            $barcodeImage = DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128');
            echo '<img style="max-width:100%; height: 43px !important; width: 293px !important;" src="data:image/png;base64,' . $barcodeImage . '" alt="barcode" />';
                        $user_role = \App\Models\User::find($lims_sale_data->user_id);


        ?>
        <br>
        <h3><?php echo $lims_sale_data->reference_no; ?></h3>
        <br>
    </td>
    <td style="border:3px solid black; border-top:3px solid black; text-align: center; font-weight: 900;" width="25%" colspan="2">
        {{date("Y-m-d g:i A",strtotime($lims_sale_data->created_at))}}<br>{{$user_role->name}}
    </td>
</tr>

              <tr>
                <table style="max-width: 1000px;
    margin: 0 auto; border-collapse: collapse;">
    <td style="border:3px solid black; text-align: center; width: 17%; font-weight: 900; " >{{$lims_customer_data->phone_number2}}</td>
    <td style="border:3px solid black; text-align: center; width: 8%; font-weight: 900; " > موبايل 2 </td>
    <td style="border:3px solid black; text-align: center; width: 17%; font-weight: 900; " colspan="2">{{$lims_customer_data->phone_number}}</td>
    <td style="border:3px solid black; text-align: center; width: 8%; font-weight: 900; " > موبايل 1 </td>
    <td style="border:3px solid black; text-align: center; width: 20%; font-weight: 900; " colspan="4">{{$lims_customer_data->name}}</td>
    <td style="border:3px solid black; text-align: center; width: 11.5%; font-weight: 900; " > الاسم </td>
</tr>

 </table>
 <table style="max-width: 1000px;
    margin: 0 auto; border-collapse: collapse;">

                <!-- صف اهت الأول -->

                <tr>
                    <td style="border:3px solid black;border-top:3px solid black;text-align: center; font-weight: 900; "colspan="7.5">{{$lims_customer_data->address}}, {{$lims_customer_data->city}} ,{{$lims_customer_data->state}}</td>
                    <td style="border:3px solid black;border-top:3px solid black;text-align: center; font-weight: 900; " colspan="6">العنوان </td>
                    
                </tr>
               
                    


                                    <tr class="table-header" style="background-color: rgb(1, 75, 148); color: white;">
                
                <td style="border:3px solid #222;padding:3px 3px;width:49%;text-align:center; font-weight: 900; ">{{trans('file.Description')}}</td>
                <td style="border:3px solid #222;padding:3px 3px;width:6%;text-align:center; font-weight: 900; " colspan="6">{{trans('file.Qty')}}</td>
                <td style="border:3px solid #222;padding:3px 3px;width:9%;text-align:center; font-weight: 900; " colspan="2">{{trans('file.Unit Price')}}</td>
                
                
            </tr>
             
          <?php
          
                $total_product_tax = 0;
                $totalPrice = 0;
                $totalQuantity = 0;
          ?>
            @foreach($lims_product_sale_data as $key => $product_sale_data)
            <?php
                $lims_product_data = \App\Models\Product::find($product_sale_data->product_id);
                if($product_sale_data->sale_unit_id) {
                    $unit = \App\Models\Unit::select('unit_code')->find($product_sale_data->sale_unit_id);
                    $unit_code = $unit->unit_code;
                }
                else
                    $unit_code = '';

                if($product_sale_data->variant_id) {
                    $variant = \App\Models\Variant::select('name')->find($product_sale_data->variant_id);
                    if($variant)
                    $variant_name = $variant->name;
                    else
                    {
                    $variant = \App\Models\ProductVariant::select('item_code')->where('variant_id',$product_sale_data->variant_id)->first();
                    $variant_name = $variant->item_code;

                    }
                }
                else
                    $variant_name = '';
            ?>
            <tr>
                
                <td style="border:3px solid #222;padding:3px 3px;font-size: 15px;line-height: 1.2; text-align: center; font-weight: 900; ">
    {!! $lims_product_data->name !!}
    @if($variant_name)
        - {!! $variant_name !!}
    @endif
    @foreach($product_custom_fields as $index => $fieldName)
        <?php $field_name = str_replace(" ", "_", strtolower($fieldName)) ?>
        @if($lims_product_data->$field_name)
            @if(!$index)
                <br>{{$fieldName.': '.$lims_product_data->$field_name}}
            @else
                {{'/'.$fieldName.': '.$lims_product_data->$field_name}}
            @endif
        @endif
    @endforeach
</td>

<?php $totalQuantity = $totalQuantity+$product_sale_data->qty; 
      $totalPrice = $totalPrice + ( $product_sale_data->qty*$product_sale_data->net_unit_price);  
               ?>

                <td style="border:3px solid #222;padding:3px 3px;text-align:center font-weight: 900; " colspan="6">{{$product_sale_data->qty}}</td>
                <td style="border:3px solid #222;padding:3px 3px;text-align:center font-weight: 900; " colspan="2">{{number_format($product_sale_data->net_unit_price, $general_setting->decimal, '.', ',')}}</td>
               
            </tr>
            @endforeach
   <?php
   if(isset($lims_sale_data->order_discount_value)){
                if($lims_sale_data->order_discount_type == 'Flat'){
                    $totalPrice -= $lims_sale_data->order_discount_value;
                }
                else
                {
                    $totalPrice -= (($lims_sale_data->order_discount_value / 100) * $totalPrice);
                }
            }
   $totalgrandprice = $totalPrice+$lims_sale_data->shipping_cost; ?>
            <table style="max-width: 1000px;
    margin: 0 auto; border-collapse: collapse;">
            <tr>
                    <td style="border:3px solid black;border-top:3px solid black;text-align: center; font-weight: 900; " width="5%" >{{number_format((float)(($totalgrandprice) - ($lims_sale_data->total_tax+$lims_sale_data->order_tax) ) ,$general_setting->decimal, '.', ',')}}</td>
    <td style="border:3px solid black;border-top:3px solid black;text-align: center; font-weight: 900; " width="25%" colspan="2"> {{trans('file.Subtotal')}} </td>
     <td style="border:3px solid black;border-top:3px solid black;text-align: center; font-weight: 900; " width="5%" >{{number_format($lims_sale_data->shipping_cost, $general_setting->decimal, '.', '')}}</td>
    <td style="border:3px solid black;border-top:3px solid black;text-align: center; font-weight: 900; " width="25%" colspan="2"> {{trans('file.Shipping')}} </td>

<td style="border:3px solid black;border-top:3px solid black;text-align: center;" width="5%" colspan="3">
            <?php 
            if(isset($lims_sale_data->order_discount_value)){
                if($lims_sale_data->order_discount_type == 'Flat'){
                    echo $lims_sale_data->order_discount_value;
                }
                else
                {
                    echo (($lims_sale_data->order_discount_value / 100) * $totalgrandprice);
                }
            }
            else
            {
                echo 'لا يوجد';
            }
            
            ?></td>
    <td style="border:3px solid black;border-top:3px solid black;text-align: center;" width="25%" colspan="3"> {{trans(' اجمالي الخصم')}} </td>

<td style="border:3px solid black;border-top:3px solid black;text-align: center; font-weight: 900; " width="5%" colspan="3">{{$totalQuantity}}</td>
    <td style="border:3px solid black;border-top:3px solid black;text-align: center; font-weight: 900; " width="25%" colspan="3"> {{trans(' اجمل عدد القطع')}} </td>
                    
                </tr>
            <tr>
                 <td colspan="16" rowspan="2.2" style="border:2px solid #222;padding:3px 3px;text-align: center; font-weight: 900;  vertical-align: top;">
                    {{trans(' الاستلام الجزئي بالسعر الاساسى بدون الخصم وفي حالة وجود أي مشكلة برجاء التواصل واتساب  01277466335 // 01277466229')}}

                </td>
                
                
            </tr>
            
            <tr>
                
            </tr>
            
            
            
                    
            <tr><td colspan="8" rowspan="8" height="300px"></td></tr>
        </table>
  

          <?php
          // Thats My Stop
        }
        else{
?>

<div style="max-width:<?php 
        if($lims_pos_setting_data->invoice_option == 'A4') { echo "1000px;";}else{ echo "400px;";} ?>margin:0 auto">


    <div id="receipt-data">
        <div class="centered">
            @if($general_setting->site_logo)
                <img src="{{url('logo', $general_setting->site_logo)}}" height="42" width="50" style="margin:10px 0;">
            @endif

            <h2>{{$lims_biller_data->company_name}}</h2>

            <p>{{trans('file.Address')}}: {{$lims_warehouse_data->address}}
                <br>{{trans('file.Phone Number')}}: {{$lims_warehouse_data->phone}}
                @if($general_setting->vat_registration_number)
                <br>{{trans('file.VAT Number')}}: {{$general_setting->vat_registration_number}}
                @endif
            </p>
        </div>
        <p>{{trans('file.Date')}}: {{date($general_setting->date_format, strtotime($lims_sale_data->created_at->toDateString()))}}<br>
            {{trans('file.reference')}}: {{$lims_sale_data->reference_no}}<br>
            {{trans('file.customer')}}: {{$lims_customer_data->name}}
            @if($lims_sale_data->table_id)
            <br>{{trans('file.Table')}}: {{$lims_sale_data->table->name}}
            <br>{{trans('file.Queue')}}: {{$lims_sale_data->queue}}
            @endif
            <?php
                foreach($sale_custom_fields as $key => $fieldName) {
                    $field_name = str_replace(" ", "_", strtolower($fieldName));
                    echo '<br>'.$fieldName.': ' . $lims_sale_data->$field_name;
                }
                foreach($customer_custom_fields as $key => $fieldName) {
                    $field_name = str_replace(" ", "_", strtolower($fieldName));
                    echo '<br>'.$fieldName.': ' . $lims_customer_data->$field_name;
                }
            ?>

        </p>
        <table class="table-data">
            <tbody>
                <?php $total_product_tax = 0;?>
                @foreach($lims_product_sale_data as $key => $product_sale_data)
                <?php
                    $lims_product_data = \App\Models\Product::find($product_sale_data->product_id);
                    if($product_sale_data->variant_id) {
                        $variant_data = \App\Models\Variant::find($product_sale_data->variant_id);
                        $product_name = $lims_product_data->name.' ['.$variant_data->name.']';
                    }
                    elseif($product_sale_data->product_batch_id) {
                        $product_batch_data = \App\Models\ProductBatch::select('batch_no')->find($product_sale_data->product_batch_id);
                        $product_name = $lims_product_data->name.' ['.trans("file.Batch No").':'.$product_batch_data->batch_no.']';
                    }
                    else
                        $product_name = $lims_product_data->name;

                    if($product_sale_data->imei_number) {
                        $product_name .= '<br>'.trans('IMEI or Serial Numbers').': '.$product_sale_data->imei_number;
                    }
                ?>
                <tr>
                    <td colspan="2">
                        {!!$product_name!!}
                        @foreach($product_custom_fields as $index => $fieldName)
                            <?php $field_name = str_replace(" ", "_", strtolower($fieldName)) ?>
                            @if($lims_product_data->$field_name)
                                @if(!$index)
                                <br>{{$fieldName.': '.$lims_product_data->$field_name}}
                                @else
                                {{'/'.$fieldName.': '.$lims_product_data->$field_name}}
                                @endif
                            @endif
                        @endforeach
                        <br>{{$product_sale_data->qty}} x {{number_format((float)($product_sale_data->total / $product_sale_data->qty), $general_setting->decimal, '.', ',')}}

                        @if($product_sale_data->tax_rate)
                            <?php $total_product_tax += $product_sale_data->tax ?>
                            [{{trans('file.Tax')}} ({{$product_sale_data->tax_rate}}%): {{$product_sale_data->tax}}]
                        @endif
                    </td>
                    <td style="text-align:right;vertical-align:bottom">{{number_format((float)($product_sale_data->total), $general_setting->decimal, '.', ',')}}</td>
                </tr>
                @endforeach

            <!-- <tfoot> -->
                <tr>
                    <th colspan="2" style="text-align:left">{{trans('file.Total')}}</th>
                    <th style="text-align:right">{{number_format((float)($lims_sale_data->total_price), $general_setting->decimal, '.', ',')}}</th>
                </tr>
                @if($general_setting->invoice_format == 'gst' && $general_setting->state == 1)
                <tr>
                    <td colspan="2">IGST</td>
                    <td style="text-align:right">{{number_format((float)($total_product_tax), $general_setting->decimal, '.', ',')}}</td>
                </tr>
                @elseif($general_setting->invoice_format == 'gst' && $general_setting->state == 2)
                <tr>
                    <td colspan="2">SGST</td>
                    <td style="text-align:right">{{number_format((float)($total_product_tax / 2), $general_setting->decimal, '.', ',')}}</td>
                </tr>
                <tr>
                    <td colspan="2">CGST</td>
                    <td style="text-align:right">{{number_format((float)($total_product_tax / 2), $general_setting->decimal, '.', ',')}}</td>
                </tr>
                @endif
                @if($lims_sale_data->order_tax)
                <tr>
                    <th colspan="2" style="text-align:left">{{trans('file.Order Tax')}}</th>
                    <th style="text-align:right">{{number_format((float)($lims_sale_data->order_tax), $general_setting->decimal, '.', ',')}}</th>
                </tr>
                @endif
                @if($lims_sale_data->order_discount)
                <tr>
                    <th colspan="2" style="text-align:left">{{trans('file.Order Discount')}}</th>
                    <th style="text-align:right">{{number_format((float)($lims_sale_data->order_discount), $general_setting->decimal, '.', ',')}}</th>
                </tr>
                @endif
                @if($lims_sale_data->coupon_discount)
                <tr>
                    <th colspan="2" style="text-align:left">{{trans('file.Coupon Discount')}}</th>
                    <th style="text-align:right">{{number_format((float)($lims_sale_data->coupon_discount), $general_setting->decimal, '.', ',')}}</th>
                </tr>
                @endif
                @if($lims_sale_data->shipping_cost)
                <tr>
                    <th colspan="2" style="text-align:left">{{trans('file.Shipping Cost')}}</th>
                    <th style="text-align:right">{{number_format((float)($lims_sale_data->shipping_cost), $general_setting->decimal, '.', ',')}}</th>
                </tr>
                @endif
                <tr>
                    <th colspan="2" style="text-align:left">{{trans('file.grand total')}}</th>
                    <th style="text-align:right">{{number_format((float)($lims_sale_data->grand_total), $general_setting->decimal, '.', ',')}}</th>
                </tr>
                <tr>
                    @if($general_setting->currency_position == 'prefix')
                    <th class="centered" colspan="3">{{trans('file.In Words')}}: <span>{{$currency_code}}</span> <span>{{str_replace("-"," ",$numberInWords)}}</span></th>
                    @else
                    <th class="centered" colspan="3">{{trans('file.In Words')}}: <span>{{str_replace("-"," ",$numberInWords)}}</span> <span>{{$currency_code}}</span></th>
                    @endif
                </tr>
            </tbody>
            <!-- </tfoot> -->
        </table>
        <table>
            <tbody>
                @foreach($lims_payment_data as $payment_data)
                <tr style="background-color:#ddd;">
                    <td style="padding: 5px;width:30%">{{trans('file.Paid By')}}: {{$payment_data->paying_method}}</td>
                    <td style="padding: 5px;width:40%">{{trans('file.Amount')}}: {{number_format((float)($payment_data->amount), $general_setting->decimal, '.', ',')}}</td>
                    <td style="padding: 5px;width:30%">{{trans('file.Change')}}: {{number_format((float)$payment_data->change, $general_setting->decimal, '.', ',')}}</td>
                </tr>
                @endforeach
                <tr><td class="centered" colspan="3">{{trans('file.Thank you for shopping with us. Please come again')}}</td></tr>
                <tr>
                    <td class="centered" colspan="3">
                    <?php echo '<img style="margin-top:10px;" src="data:image/png;base64,' . DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128') . '" width="300" alt="barcode"   />';?>
                    <br>
                    </td>
                </tr>
                <tr><td>____________________________________________</td></tr>
            </tbody style="border-bottom: 2px solid black;">
        </table>
    </div>
    <hr>
    <?php

     }
        }

        ?>
        
</div>
</div>
<div id="editor"></div>

<script type="text/javascript">
    localStorage.clear();
    function auto_print() {
        window.print();
    }
    setTimeout(auto_print, 1000);
    

    function topdf(){
        var element = document.getElementById('content');
          var opt = {
  margin:       1,
  filename:     'Wasta-Sales.pdf',
  image:        { type: 'jpeg', quality: 0.98 },
  html2canvas:  { scale: 2 },
  jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
};

// New Promise-based usage:
html2pdf(element, opt);
}
</script>

</body>
</html>