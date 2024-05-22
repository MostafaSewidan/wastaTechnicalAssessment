@extends('backend.layout.main')
@section('content')

@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<?php

$totalPrice = 0;
$totalreturn = 0;
$totalQuantity = 0;
?>
<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{trans('file.Partial Return')}}</h3>
            </div>

    <div class="table-responsive">
        <table  class="table" style="width: 100%">

                <tr>
    <td colspan="6" >
        <br>
        <?php
            $barcodeImage = DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128');
            echo '<img style="max-width:100%; height: 62px !important; width: 423px !important;" src="data:image/png;base64,' . $barcodeImage . '" alt="barcode" />';
        ?>
        <br>
        <h3><?php echo $lims_sale_data->reference_no; ?></h3>
        <br>
    </td>
    <td  width="25%" colspan="2">
        {{$lims_sale_data->created_at}}<br>{{$role->name}}
    </td>
</tr>

              <tr>
    <td >{{$lims_customer_data->phone_number}}</td>
    <td > موبايل 2 </td>
    <td colspan="2">{{$lims_customer_data->phone_number}}</td>
    <td > موبايل 1 </td>
    <td colspan="4">{{$lims_customer_data->name}}</td>
    <td > لسم </td>
</tr>
</table>

 
            </div>
        </div>
    <div class="table-responsive">
        <table  class="table" style="width: 100%">
                 {!! Form::open(['route' => 'sale.updatepart', 'method' => 'post', 'files' => true, 'class' => 'payment-form']) !!}
                <input type="hidden" name="sale_id" value="{{ $lims_sale_data->id }}">

            <thead>
                <tr>
                    <th>{{trans('file.name')}}</th>
                    <th>{{trans('file.Quantity')}}</th>
                    <th>{{trans('file.Price')}}</th>
                    <th>{{trans('file.Is Damaged')}}</th>
                    <th>{{trans('file.Damage Reason')}}</th>
                    <th>{{trans('file.Return Subtotal')}}</th>
                    <th>{{trans('file.Total')}}</th>
                    <th class="not-exported">{{trans('file.returned quantity')}}</th>
                </tr>
            </thead>
            <tbody>


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
                    $variant_name = '';

                }
                else
                    $variant_name = '';
            ?>
            <tr class="product-link">
                
                <td >
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
      $totalreturn = $totalreturn + ( $product_sale_data->return_qty*$product_sale_data->net_unit_price);       ?>
                <td>{{$product_sale_data->qty}}</td>
                <td>{{number_format($product_sale_data->net_unit_price, $general_setting->decimal, '.', ',')}}</td>
                <td><input type="checkbox" class="form-control" {{ $product_sale_data->is_damaged === 1 ? 'checked' : '' }} id="damage" value="1" name="damage[{{$product_sale_data->id}}]"  /></td>
                <td>
                    <input type="hidden" name="damage_reason[{{$product_sale_data->id}}]" id="damage_reason_{{$product_sale_data->id}}" value="{{$product_sale_data->damage_reason}}">
                    <select  name="reason[{{$product_sale_data->id}}]" onchange="updatevalue({{$product_sale_data->id}});" class="selectpicker form-control" data-live-search="true" id="reason_{{$product_sale_data->id}}">
                    <option {{ $product_sale_data->damage_reason === 0 ? 'selected' : '' }}  value="0">{{trans('please select')}}</option>
                    <option {{ $product_sale_data->damage_reason === 1 ? 'selected' : '' }}  value="1">{{trans('file.Warehouse')}}</option>
                    <option {{ $product_sale_data->damage_reason === 2 ? 'selected' : '' }}  value="2">{{trans('Shipping Company')}}</option>
                    <option {{ $product_sale_data->damage_reason === 3 ? 'selected' : '' }}  value="3">{{trans('Client')}}</option>
                    <option {{ $product_sale_data->damage_reason === 4 ? 'selected' : '' }}  value="4">{{trans('Unknown')}}</option>
                </select>
            </td>
                <td>{{number_format($product_sale_data->return_qty*$product_sale_data->net_unit_price, $general_setting->decimal, '.', ',')}}</td>
                <td>{{number_format($product_sale_data->qty * $product_sale_data->net_unit_price, $general_setting->decimal, '.', ',')}}</td>
                <td><input type="number" name="returned[{{$product_sale_data->id}}]" value="{{$product_sale_data->return_qty}}" class="form-control" ></td>
               
            </tr>
            @endforeach


            </tbody>

            <tfoot class="tfoot active">
                <th>{{trans('file.Total')}}</th>
                <th> {{ $totalQuantity }}</th>
                <th></th>
                <th></th>
                <th></th>
                <th>{{number_format($totalreturn, $general_setting->decimal, '.', ',')}}</th>
                <th>{{number_format($totalPrice, $general_setting->decimal, '.', ',')}}</th>
                <th><button class="btn btn-primary" type="submit">{{trans('file.Submit')}}</button></th>
            </tfoot>
                {{ Form::close() }}
        </table>
    </div>
    <script type="text/javascript">
        function updatevalue(id){
          var damage_reason =  document.getElementById('damage_reason_'+id) ;
          var reason =  document.getElementById('reason_'+id).value ;
           damage_reason.value = reason ;


        }
    </script>
@endsection