@extends('backend.layout.main') @section('content')
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('file.Add Return')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'return-purchase.store', 'method' => 'post', 'files' => true, 'class' => 'payment-form']) !!}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-12">
                                        <input type="hidden" name="purchase_id" value="{{$lims_purchase_data->id}}">
                                        <h5>{{trans('file.Order Table')}} *</h5>
                                        <div class="table-responsive mt-3">
                                            <table id="myTable" class="table table-hover order-list">
                                                <thead>
                                                    <tr>
                                                        <th>{{trans('file.name')}}</th>
                                                        <th>{{trans('file.Code')}}</th>
                                                        <th>{{trans('file.Batch No')}}</th>
                                                        <th>{{trans('file.Quantity')}}</th>
                                                        <th>{{trans('file.Net Unit Cost')}}</th>
                                                        <th>{{trans('file.Discount')}}</th>
                                                        <th>{{trans('file.Tax')}}</th>
                                                        <th>{{trans('file.Subtotal')}}</th>
                                                        <th>Choose</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($lims_product_purchase_data as $product_purchase)
                                                    <tr>
                                                    <?php
                                                        $product_data = DB::table('products')->find($product_purchase->product_id);
                                                        if($product_purchase->variant_id) {
                                                            $product_variant_data = \App\Models\ProductVariant::select('id', 'item_code')->FindExactProduct($product_data->id, $product_purchase->variant_id)->first();
                                                            $product_variant_id = $product_variant_data->id;
                                                            $product_data->code = $product_variant_data->item_code;
                                                        }
                                                        else
                                                            $product_variant_id = null;
                                                        if($product_data->tax_method == 1){
                                                            $product_cost = $product_purchase->net_unit_cost + ($product_purchase->discount / $product_purchase->qty);
                                                        }
                                                        elseif ($product_data->tax_method == 2) {
                                                            $product_cost =($product_purchase->total / $product_purchase->qty) + ($product_purchase->discount / $product_purchase->qty);
                                                        }

                                                        $tax = DB::table('taxes')->where('rate',$product_purchase->tax_rate)->first();
                                                        if($product_data->type == 'standard'){
                                                            $unit = DB::table('units')->select('unit_name')->find($product_data->unit_id);
                                                           $unit_name = $unit->unit_name;
                                                        }
                                                        else {
                                                            $unit_name = 'n/a';
                                                        }
                                                        $product_batch_data = \App\Models\ProductBatch::select('batch_no')->find($product_purchase->product_batch_id);
                                                    ?>
                                                        <td>{{$product_data->name}}</td>
                                                        <td>{{$product_data->code}}</td>
                                                        @if($product_batch_data)
                                                        <td>
                                                            <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="{{$product_purchase->product_batch_id}}">
                                                            {{$product_batch_data->batch_no}}
                                                        </td>
                                                        @else
                                                        <td>
                                                            <input type="hidden" class="product-batch-id" name="product_batch_id[]" >
                                                            N/A
                                                        </td>
                                                        @endif
                                                        <td>
                                                            <input type="hidden" name="actual_qty[]" class="actual-qty" value="{{$product_purchase->qty}}">
                                                            <input type="number" class="form-control qty" name="qty[]" value="{{$product_purchase->qty}}" required step="any" max="{{$product_purchase->qty}}" />
                                                        </td>
                                                        <td class="net_unit_cost">{{ number_format((float)$product_purchase->net_unit_cost, $general_setting->decimal, '.', '')}} </td>
                                                        <td class="discount">{{ number_format((float)$product_purchase->discount, $general_setting->decimal, '.', '')}}</td>
                                                        <td class="tax">{{ number_format((float)$product_purchase->tax, $general_setting->decimal, '.', '')}}</td>
                                                        <td class="sub-total">{{ number_format((float)$product_purchase->total, $general_setting->decimal, '.', '')}}</td>
                                                        <td><input type="checkbox" class="is-return" name="is_return[]" value="{{$product_data->id}}"></td>
                                                        <input type="hidden" class="product-code" name="product_code[]" value="{{$product_data->code}}"/>
                                                        <input type="hidden" name="product_id[]" class="product-id" value="{{$product_data->id}}"/>
                                                        <input type="hidden" class="unit-cost" value="{{$product_purchase->total/$product_purchase->qty}}">
                                                        <input type="hidden" name="product_variant_id[]" value="{{$product_variant_id}}"/>
                                                        <input type="hidden" class="product-cost" name="product_cost[]" value="{{$product_cost}}"/>
                                                        <input type="hidden" class="purchase-unit" name="purchase_unit[]" value="{{$unit_name}}"/>
                                                        <input type="hidden" class="net_unit_cost" name="net_unit_cost[]" value="{{$product_purchase->net_unit_cost}}" />
                                                        <input type="hidden" class="discount-value" name="discount[]" value="{{$product_purchase->discount}}" />
                                                        <input type="hidden" class="tax-rate" name="tax_rate[]" value="{{$product_purchase->tax_rate}}"/>
                                                        @if($tax)
                                                        <input type="hidden" class="tax-name" value="{{$tax->name}}" />
                                                        @else
                                                        <input type="hidden" class="tax-name" value="No Tax" />
                                                        @endif
                                                        <input type="hidden" class="tax-method" value="{{$product_data->tax_method}}"/>
                                                        <input type="hidden" class="unit-tax-value" value="{{$product_purchase->tax / $product_purchase->qty}}" />
                                                        <input type="hidden" class="tax-value" name="tax[]" value="{{$product_purchase->tax}}" />
                                                        <input type="hidden" class="subtotal-value" name="subtotal[]" value="{{$product_purchase->total}}" />
                                                        <input type="hidden" class="imei-number" name="imei_number[]" value="{{$product_purchase->imei_number}}" />
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_qty" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_discount" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_tax" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_cost" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="item" />
                                            <input type="hidden" name="order_tax" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="grand_total" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Account')}}</label>
                                            <select class="form-control" name="account_id">
                                                @foreach($lims_account_list as $account)
                                                <option value="{{$account->id}}">{{$account->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Order Tax')}}</label>
                                            <select class="form-control" name="order_tax_rate">
                                                <option value="0">No Tax</option>
                                                @foreach($lims_tax_list as $tax)
                                                <option value="{{$tax->rate}}">{{$tax->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                	<div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Attach Document')}}</label>
                                            <i class="dripicons-question" data-toggle="tooltip" title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                            <input type="file" name="document" class="form-control" />
                                            @if($errors->has('extension'))
                                                <span>
                                                   <strong>{{ $errors->first('extension') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{trans('file.Return Note')}}</label>
                                            <textarea rows="5" class="form-control" name="return_note"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{trans('file.Staff Note')}}</label>
                                            <textarea rows="5" class="form-control" name="staff_note"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary" id="submit-button">
                                </div>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <table class="table table-bordered table-condensed totals">
            <td><strong>{{trans('file.Items')}}</strong>
                <span class="pull-right" id="item">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>
            <td><strong>{{trans('file.Total')}}</strong>
                <span class="pull-right" id="subtotal">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>
            <td><strong>{{trans('file.Order Tax')}}</strong>
                <span class="pull-right" id="order_tax">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>
            <td><strong>{{trans('file.grand total')}}</strong>
                <span class="pull-right" id="grand_total">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>
        </table>
    </div>

</section>

@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#return").siblings('a').attr('aria-expanded','true');
    $("ul#return").addClass("show");
    $("ul#return #purchase-return-menu").addClass("active");

var product_code = [];
var product_cost = [];
var product_discount = [];
var tax_rate = [];
var tax_name = [];
var tax_method = [];
var unit_name = [];
var unit_operator = [];
var unit_operation_value = [];
var is_imei = [];

// temporary array
var temp_unit_name = [];
var temp_unit_operator = [];
var temp_unit_operation_value = [];

var rowindex;
var row_product_cost;

$('.selectpicker').selectpicker({
    style: 'btn-link',
});

$('[data-toggle="tooltip"]').tooltip();

//choosing the returned product
$("#myTable").on("change", ".is-return", function () {
    calculateTotal();
});

//Change quantity
$("#myTable").on('input', '.qty', function() {
    rowindex = $(this).closest('tr').index();
    if($(this).val() < 1 && $(this).val() != '') {
      $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(1);
      alert("Quantity can't be less than 1");
    }
    calculateTotal();
});

$('select[name="order_tax_rate"]').on("change", function() {
    calculateGrandTotal();
});

function calculateTotal() {
    var total_qty = 0;
    var total_discount = 0;
    var total_tax = 0;
    var total = 0;
    var item = 0;
    $(".is-return").each(function(i) {
        if ($(this).is(":checked")) {
            var actual_qty = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .actual-qty').val();
            var qty = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .qty').val();
            if(qty > actual_qty) {
                alert('Quantity can not be bigger than the actual quantity!');
                qty = actual_qty;
                $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .qty').val(actual_qty);
            }
            var discount = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .discount').text();
            var tax = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .unit-tax-value').val() * qty;
            var unit_cost = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .unit-cost').val();

            total_qty += parseFloat(qty);
            total_discount += parseFloat(discount);
            total_tax += parseFloat(tax);
            total += parseFloat(unit_cost * qty);
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .subtotal-value').val(unit_cost * qty);
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .sub-total').text(parseFloat(unit_cost * qty).toFixed({{$general_setting->decimal}}));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .tax-value').val(parseFloat(tax).toFixed({{$general_setting->decimal}}));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .tax').text(parseFloat(tax).toFixed({{$general_setting->decimal}}));
            item++;
        }
    });
    $('input[name="total_qty"]').val(total_qty);

    $('input[name="total_discount"]').val(total_discount.toFixed({{$general_setting->decimal}}));

    $('input[name="total_tax"]').val(total_tax.toFixed({{$general_setting->decimal}}));

    $('input[name="total_cost"]').val(total.toFixed({{$general_setting->decimal}}));
    $('input[name="item"]').val(item);
    item += '(' + total_qty + ')';
    $('#item').text(item);

    calculateGrandTotal();
}

function calculateGrandTotal() {
    var total_qty = parseFloat($('input[name="total_qty"]').val());
    var subtotal = parseFloat($('input[name="total_cost"]').val());
    var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
    var order_tax = subtotal * (order_tax / 100);
    var grand_total = subtotal + order_tax;


    $('#subtotal').text(subtotal.toFixed({{$general_setting->decimal}}));
    $('#order_tax').text(order_tax.toFixed({{$general_setting->decimal}}));
    $('input[name="order_tax"]').val(order_tax.toFixed({{$general_setting->decimal}}));
    $('#grand_total').text(grand_total.toFixed({{$general_setting->decimal}}));
    $('input[name="grand_total"]').val(grand_total.toFixed({{$general_setting->decimal}}));
}

$(window).keydown(function(e){
    if (e.which == 13) {
        var $targ = $(e.target);
        if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
            var focusNext = false;
            $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
                if (this === e.target) {
                    focusNext = true;
                }
                else if (focusNext){
                    $(this).focus();
                    return false;
                }
            });
            return false;
        }
    }
});

$('.payment-form').on('submit',function(e){
    var rownumber = $('table.order-list tbody tr:last').index();
    if (rownumber < 0) {
        alert("Please insert product to order table!")
        e.preventDefault();
    }
    else {
        $("#submit-button").prop('disabled', true);
    }
});

</script>
@endpush
