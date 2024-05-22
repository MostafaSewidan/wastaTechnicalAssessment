 <!DOCTYPE html>
<html>
  <head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" type="image/png" href="{{url('logo', $general_setting->site_logo)}}" />
    <title>{{$general_setting->site_title}}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
@php
        $totalOrders = 0;
    @endphp
    <style type="text/css">
        @media print {
  @page {
    margin-top: 0;
    margin-bottom: 0;
  }
  body  {
    padding-top: 5rem;
    padding-bottom: 5rem;
  }
}
        * {
            font-size: 14px;
            line-height: 24px;
            font-family: 'Ubuntu', sans-serif;
            text-transform: capitalize;
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
        tr {border-bottom: 1px dotted #ddd;}
        td,th {padding: 7px 0;width: 9.6%;}

        table {width: 100%;}
        tfoot tr th:first-child {text-align: left;}

        .centered {
            text-align: center;
            align-content: center;
        }
        small{font-size:11px;}

        @media print {
            * {
                font-size:12px;
                line-height: 20px;
            }
            td,th {padding: 5px 0;}
            .hidden-print {
                display: none !important;
            }
            @page { margin: 1.5cm 0.5cm 0.5cm; }
            @page:first { margin-top: 0.5cm; }
            /*tbody::after {
                content: ''; display: block;
                page-break-after: always;
                page-break-inside: avoid;
                page-break-before: avoid;
            }*/
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
            </tr>
        </table>
        <br>
    </div>
    @foreach($lims_product_sale_data as $key => $product_sale_data)
            <?php
                // الأكواد الحالية للحصول على البيانات
                $totalOrders += 1; // زيادة عدد الطلب
            ?>
            <!-- الأكواد الحالي لعرض الطلبات -->
        @endforeach
    
    <table style="max-width: 1000px;
    margin: 0 auto; border-collapse: collapse;">

                <tr>
    <td colspan="6" style="border:3px solid black; text-align: center; line-height: 0px; width: 86%; height: 30px;">
        <br>
        <?php
            $barcodeImage = DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128');
            echo '<img style="max-width:100%; height: 62px !important; width: 423px !important;" src="data:image/png;base64,' . $barcodeImage . '" alt="barcode" />';
            $user_role = \Spatie\Permission\Models\Role::find(\App\Models\User::find($lims_sale_data->user_id)->role_id);
        ?>
        <br>
        <h3><?php echo $lims_sale_data->reference_no; ?></h3>
        <br>
    </td>
    <td style="border:3px solid black; border-top:3px solid black; text-align: center;" width="25%" colspan="2">
        {{date("Y-m-d g:i A",strtotime($lims_sale_data->created_at))}}<br>{{$user_role->name}}
    </td>
</tr>

              <tr>
                <table style="max-width: 1000px;
    margin: 0 auto; border-collapse: collapse;">
    <td style="border:3px solid black; text-align: center; width: 17%;" >{{$lims_customer_data->phone_number2}}</td>
    <td style="border:3px solid black; text-align: center; width: 8%;" > موبايل 2 </td>
    <td style="border:3px solid black; text-align: center; width: 17%;" colspan="2">{{$lims_customer_data->phone_number}}</td>
    <td style="border:3px solid black; text-align: center; width: 8%;" > موبايل 1 </td>
    <td style="border:3px solid black; text-align: center; width: 20%;" colspan="4">{{$lims_customer_data->name}}</td>
    <td style="border:3px solid black; text-align: center; width: 11.5%;" > اسم </td>
</tr>

 </table>
 <table style="max-width: 1000px;
    margin: 0 auto; border-collapse: collapse;">

                <!-- صف الهاتف الأول -->

                <tr>
                    <td style="border:3px solid black;border-top:1px solid black;text-align: center;"colspan="7.5">{{$lims_customer_data->address}}</td>
                    <td style="border:3px solid black;border-top:1px solid black;text-align: center;" colspan="6">العنوان </td>
                    
                </tr>
               
                    


                                    <tr class="table-header" style="background-color: rgb(1, 75, 148); color: white;">
                
                <td style="border:3px solid #222;padding:1px 3px;width:49%;text-align:center;">{{trans('file.Description')}}</td>
                <td style="border:3px solid #222;padding:1px 3px;width:6%;text-align:center;" colspan="6">{{trans('file.Qty')}}</td>
                <td style="border:3px solid #222;padding:1px 3px;width:9%;text-align:center;" colspan="2">{{trans('file.Unit Price')}}</td>
                
                
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
                    else{
                      $variant_data = \App\Models\ProductVariant::select('item_code')->where('variant_id',$product_sale_data->variant_id)->where('product_id',$product_sale_data->product_id)->first();
                      if($variant_data){
                      $variant_id = \App\Models\ProductVariant::select('variant_id')->where('item_code',$variant_data->item_code)->orderby('variant_id','ASC')->first();
                      
                    $variant = \App\Models\Variant::select('name')->find($variant_id->variant_id);
                   if($variant)
                    $variant_name = $variant->name;
                    else
                    $variant_name = '';
                      }
                      else
                    $variant_name = '';
                    }
                }
                else
                    $variant_name = '';
            ?>
            <tr>
                
                <td style="border:3px solid #222;padding:1px 3px;font-size: 15px;line-height: 1.2; text-align: center;">
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
      $totalPrice = $totalPrice + ( $product_sale_data->qty*$product_sale_data->net_unit_price);       ?>
                <td style="border:3px solid #222;padding:1px 3px;text-align:center" colspan="6">{{$product_sale_data->qty}}</td>
                <td style="border:3px solid #222;padding:1px 3px;text-align:center" colspan="2">{{number_format($product_sale_data->net_unit_price, $general_setting->decimal, '.', ',')}}</td>
               
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
            
            ?>
            <table style="max-width: 1000px;
    margin: 0 auto; border-collapse: collapse;">
            <tr>
                    <td style="border:3px solid black;border-top:1px solid black;text-align: center;" width="5%" >{{number_format((float)(($totalPrice+$lims_sale_data->shipping_cost) - ($lims_sale_data->total_tax+$lims_sale_data->order_tax) ) ,$general_setting->decimal, '.', ',')}}</td>
    <td style="border:3px solid black;border-top:1px solid black;text-align: center;" width="25%" colspan="2"> {{trans('file.Subtotal')}} </td>
     <td style="border:3px solid black;border-top:1px solid black;text-align: center;" width="5%" >{{number_format($lims_sale_data->shipping_cost, $general_setting->decimal, '.', '')}}</td>
    <td style="border:3px solid black;border-top:1px solid black;text-align: center;" width="25%" colspan="2"> {{trans('file.Shipping')}} </td>

<td style="border:3px solid black;border-top:1px solid black;text-align: center;" width="5%" colspan="3">
            <?php 
            if(isset($lims_sale_data->order_discount_value)){
                if($lims_sale_data->order_discount_type == 'Flat'){
                    echo $lims_sale_data->order_discount_value;
                }
                else
                {
                    echo (($lims_sale_data->order_discount_value / 100) * $totalPrice);
                }
            }
            else
            {
                echo 'لا يوجد';
            }
            
            ?></td>
    <td style="border:3px solid black;border-top:1px solid black;text-align: center;" width="25%" colspan="3"> {{trans(' اجمالي الخصم')}} </td>
<td style="border:3px solid black;border-top:1px solid black;text-align: center;" width="5%" colspan="3">{{$totalQuantity}}</td>
    <td style="border:3px solid black;border-top:1px solid black;text-align: center;" width="25%" colspan="3"> {{trans(' اجمالي عدد القطع')}} </td>
                    
                </tr>
            <tr>
                 <td colspan="16" rowspan="2.2" style="border:2px solid #222;padding:3px 3px;text-align: center; font-weight: 900;  vertical-align: top;">
                    {{trans('ممنوع فتح الشحنه حفاظا على سلامه وسريه الاوردر الخاص بكم  و في حالة وجود أي مشكلة برجاء التواصل واتساب  01006551391 // 01277466229')}}

                </td>
                
                
            </tr>
            
            <tr>
                
            </tr>
            
            
            
                    
                </tr>
            <tr><td colspan="8" rowspan="8" height="300px"></td></tr>
        </table>
        
        <script type="text/javascript">
            localStorage.clear();
            function auto_print() {
                window.print();

            }
            setTimeout(auto_print, 1000);
        </script>
    </body>
</html>
