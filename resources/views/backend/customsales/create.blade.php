@extends('backend.layout.top-head')
@section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('not_permitted') !!}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h3>Add Custom Sale</h3>
                    </div>
                    <div class="card-body">
                        {!! Form::open(['route' => 'customsales.store', 'method' => 'post' ,'class' => 'payment-form', 'files' => 'true']) !!}
                    <div class="container">
                            <div class="row">
                        @php
                            $customer_active = DB::table('permissions')
                              ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                              ->where([
                                ['permissions.name', 'customers-add'],
                                ['role_id', \Auth::user()->role_id] ])->first();
                        @endphp
                                    <style>
                                        .inputcustomer  .dropdown-toggle.bs-placeholder {
                                            display:none!important;
                                        }
                                        .inputcustomer  .select2-selection--single {
                                              width: 100%!important;
                                              height: 112%!important;
                                                background-color: transparent!important;
                                                border: none!important;
                                        }
                                        .inputcustomer  .select2-container {
                                              width: 100%!important;
                                        }
                                        .inputcustomer  .select2-selection__arrow {
                                              width: 100%!important;
                                        }
                                        .inputcustomer  .select2-selection__arrow b {
                                              opacity: 0!important;
                                        }
                                        .inputcustomer  .btn-link {
                                              display: none!important;
                                        }
                                    </style>
                
                        
                                    <div class="col-md-4">
                                        <div class="form-group inputcustomer">
                                            @if($lims_pos_setting_data)
                                            <input type="hidden" name="customer_id_hidden" value="{{$lims_pos_setting_data->customer_id}}">
                                            @endif
                                            <div class="input-group pos customer-input">
                                                @if($customer_active)
                                        <select name="customer_id" id="customer_id" class="form-control" style="width: 100%;"></select>
                                    
                                                <?php
                                                  $deposit = [];
                                                  $points = [];
                                                ?>
                                                <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#addCustomer"><i class="dripicons-plus"></i></button>
                                                @else
                                                <?php
                                                  $deposit = [];
                                                  $points = [];
                                                ?>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                        <div class="input-group">
                                            <select class="form-control selectpicker" id="address_id" data-live-search="true" placeholder="Select Address" name="address_id">
                                                <option disabled>Select Customer First</option>
                                            </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                                <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#addaddress"><i class="dripicons-plus"></i>اضافة عنوان جديد </button>
                                        
                                    </div>
                                    </div>
                            
                            <hr>
                            <div class="row">
                                
                                <div class="col-md-12" >
                                    <div class="table-responsive ml-2">
                                        <table id="variant-table" class="table table-hover order-list">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.name')}}</th>
                                                    <th>{{trans('Describtion')}}</th>
                                                    <th>{{trans('file.Qty')}}</th>
                                                    <th>{{trans('file.Image')}}</th>
                                                    <th>{{trans('file.Action')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="variant-input-section">
                                                <tr>
                                                <td><input name="product_name" class="form-control" type="text"/></td>
                                                <td><textarea style="height: 38px;" rows="1" class="form-control" name="product_desc"></textarea></td>
                                                <td><input name="product_qty" class="form-control qty" type="number" value="1"/></td>
                                                <td><input name="product_images[]" class="form-control" type="file" multiple accept="image/*" /></td>
                                                <td></td>
                                            </tr>
                                            </tbody>
                                        </table>
<!--
                                    <div class="col-md-12 form-group">
                                        <button type="button" class="btn btn-info add-more-variant"><i class="dripicons-plus"></i> {{trans('file.Add More')}}</button>
                                    </div> -->
                                    </div>
                                </div>
                            </div></div>
                            <input type="hidden" name="shipping_cost" value="0" id="shipping-cost-val">

                                <div class="col-12 totals" style="border-top: 2px solid #e4e6fc; padding-top: 10px;">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <span class="totals-title">{{trans('file.Items')}}</span><span id="item">1</span>
                                        </div>
                                        <div class="col-sm-4">
                                            <span class="totals-title">{{trans('file.Shipping')}} <button type="button" class="btn btn-link btn-sm" data-toggle="modal" data-target="#shipping-cost-modal"><i class="dripicons-document-edit"></i></button></span><span id="shipping-cost">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
                                        </div>
                                        <div class="col-sm-4">
                                            <button type="button" class="btn btn-primary" id="submit-btn">{{trans('file.submit')}}</button>
                                        </div>
                                    </div>
                                </div>
                        {!! Form::close() !!}
                    
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

            <div id="addaddress" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                <div role="document" class="modal-dialog">
                  <div class="modal-content">
                    {!! Form::open(['route' => 'sale.saveaddress', 'method' => 'post', 'files' => true, 'id' => 'address-form']) !!}
                    <div class="modal-header">
                      <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Add Customer')}}</h5>
                      <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="modal-body">
                      <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        
                        <input type="hidden" name="customer" id="customer">
                        <div class="form-group">
                            <label>{{trans('file.Address')}} *</label>
                            <input type="text" name="address_text" required class="form-control">
                        </div>
                            <div class="form-group">
    <label>{{trans('المحافظه')}} *</label>
    <select required name="state_text" id="state_text" class="selectpicker form-control" data-live-search="true" title="Select city...">
        <?php
        $states = DB::table('places')->groupby('state')->get();
        ?>
        @foreach($states as $state)
        <option value="{{ $state->state }}">{{ $state->state }}</option>
        
        @endforeach
    </select>
</div>
                        <div class="form-group">
                            <label>{{trans('file.City')}} *</label>
                            
                            <select  name="city_text" required  class=" selectpicker form-control" id="cities_text" data-live-search="true">
                                <option >اختر المحافظة اولا</option>
                                </select>
                                
                               <!-- <input name="city" type="text" class="form-control">-->
                        </div>
                        <div class="form-group">
                            <input type="hidden" name="pos" value="1">
                            <button type="button" class="btn btn-primary address-submit-btn">{{trans('file.submit')}}</button>
                        </div>
                    </div>
                    {{ Form::close() }}
                  </div>
                </div>
            </div>

            <div id="addCustomer" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                <div role="document" class="modal-dialog">
                  <div class="modal-content">
                    {!! Form::open(['route' => 'customer.store', 'method' => 'post', 'files' => true, 'id' => 'customer-form']) !!}
                    <div class="modal-header">
                      <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Add Customer')}}</h5>
                      <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="modal-body">
                      <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        <div class="form-group">
                            <label>{{trans('file.Customer Group')}} *</strong> </label>
                            <select required class="form-control selectpicker" name="customer_group_id">
                                @foreach($lims_customer_group_all as $customer_group)
                                    <option value="{{$customer_group->id}}">{{$customer_group->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{trans('file.name')}} *</strong> </label>
                            <input type="text" name="customer_name" required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>{{trans('file.Phone Number')}} *</label>
                            <input type="tel" name="phone_number"  required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{{trans('رقم هاتف ٢ان وجد')}} *</label>
                            <input type="tel" name="phone_number2"  class="form-control">
                        </div> 
                        <div class="form-group">
                            <label>{{trans('file.Address')}} *</label>
                            <input type="text" name="address" required class="form-control">
                        </div>
                            <div class="form-group">
    <label>{{trans('المحافظه')}} *</label>
    <select required name="state" id="state" class="selectpicker form-control" data-live-search="true" title="Select city...">
        <?php
        $states = DB::table('places')->groupby('state')->get();
        ?>
        @foreach($states as $state)
        <option value="{{ $state->state }}">{{ $state->state }}</option>
        
        @endforeach
    </select>
</div>
                        <div class="form-group">
                            <label>{{trans('file.City')}} *</label>
                            
                            <select  name="city" required  class=" selectpicker form-control" id="cities" data-live-search="true">
                                <option >اختر المحافظة اولا</option>
                                </select>
                                
                               <!-- <input name="city" type="text" class="form-control">-->
                        </div>
                        <div class="form-group">
                            <input type="hidden" name="pos" value="1">
                            <button type="button" class="btn btn-primary customer-submit-btn">{{trans('file.submit')}}</button>
                        </div>
                    </div>
                    {{ Form::close() }}
                  </div>
                </div>
            </div>

            <div id="shipping-cost-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                <div role="document" class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{trans('file.Shipping Cost')}}</h5>
                            <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <input type="text" name="shipping_cost_value" class="form-control numkey" id="shipping-cost-val" step="any" onkeyup='saveValue(this);'>
                            </div>
                            <button type="button" name="shipping_cost_btn" class="btn btn-primary" data-dismiss="modal">{{trans('file.submit')}}</button>
                        </div>
                    </div>
                </div>
            </div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script type="text/javascript">
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #create-sms-menu").addClass("active");

 $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

$("#submit-btn").on("click", function() {
   var shipping = $('input[name="shipping_cost"]').val();
   if(shipping > 0){
     $("#submit-btn").prop('disabled', true);
 setTimeout(function() {
       $("#submit-btn").prop('disabled', false);
 }, 5000);
    $('.payment-form').submit();
   }
   else
   {
       alert('يرجى ادخال قيمة الشحن');
   }
});
$("table.order-list tbody").on("click", ".del", function(event) {
    $(this).closest("tr").remove();
            var item = 0;


    $("table.order-list tbody tr").each(function(index) {
        
item += 1;
    });
    $('#item').text(item);
});
    $('.add-more-variant').on("click", function() {
        var item = 0;


        var htmlText = `<tr>
                                                <td><input name="product_name[]" class="form-control" type="text"/></td>
                                                <td><textarea style="height: 38px;" rows="1" class="form-control" name="product_desc[]"></textarea></td>
                                                <td><input name="product_qty[]" class="form-control qty" type="number" value="1"/></td>
                                                <td><input name="product_images[]" class="form-control" type="file" multiple accept="Image" /></td>
                                                <td><button type="button" class="btn btn-danger btn-sm del"><i class="dripicons-cross"></i></button></td>
                                            </tr>`;
        $("#variant-input-section").append(htmlText);

    $("table.order-list tbody tr").each(function(index) {
        
item += 1;
    });
    $('#item').text(item);

    });
    var total_qty = 1;
    $('.qty').on("change",function() {
    
    $(".qty").each(function(index) {
        if ($(this).val() == '') {
            total_qty += 0;

        } else {
            total_qty += parseFloat($(this).val());
        }
         
    });
    $('#total_qty').text(total_qty);
    });

$('.customer-submit-btn').on("click", function() {
    $.ajax({
        type:'POST',
        url:'{{route('customer.store')}}',
        data: $("#customer-form").serialize(),
        success:function(response) {
            key = response['id'];
            value = response['name']+' ['+response['phone_number']+']';
            $('select[name="customer_id"]').append('<option value="'+ key +'">'+ value +'</option>');
            $('select[name="customer_id"]').val(key);
            $('.selectpicker').selectpicker('refresh');
            $("#addCustomer").modal('hide');
             updateshipping(key);
        }
    });
});

$('button[name="shipping_cost_btn"]').on("click", function() {

        var shipping_cost = parseFloat($('input[name="shipping_cost_value"]').val());
$('input[name="shipping_cost"]').val(shipping_cost);
    $('#shipping-cost').text(shipping_cost);
    
});


$('.address-submit-btn').on("click", function() {
    var customer = $('#customer_id').val();
    if(customer > 0){
    $.ajax({
        type:'POST',
        url:'{{route('sale.saveaddress')}}',
        data: $("#address-form").serialize(),
        success:function(response) {
            key = response['id'];
            value = response['value'];
            $('select[name="address_id"]').empty();
            $('select[name="address_id"]').append('<option value="'+ key +'">'+ value +'</option>');
            $('select[name="address_id"]').val(key);
            $('.selectpicker').selectpicker('refresh');
            $("#addaddress").modal('hide');
        }
    });
    }
    else
    {
        alert('من فضلك اختر العميل اولا');
    }
});

        $('#state').on("change",function () {
            var countryId = $(this).val();
            $('#cities').empty();
            $.ajax({
                url: '../sales/getcities/' + countryId,
                type: 'GET',
                success: function (data) {
                    
                    $('#cities').append('<option selected>برجاء اختيار المدينه </option>');

                    $.each(data, function (key, value) {
                        $('#cities').append('<option value="' + value.city + '">' + value.city + '</option>');
                    });
                      $('.selectpicker').selectpicker("refresh");

                }
            });
        });
        $('#state_text').on("change",function () {
            var countryId = $(this).val();
            $('#cities_text').empty();
            $.ajax({
                url: '../sales/getcities/' + countryId,
                type: 'GET',
                success: function (data) {
                    
                    $('#cities_text').append('<option selected>برجاء اختيار المدينه </option>');

                    $.each(data, function (key, value) {
                        $('#cities_text').append('<option value="' + value.city + '">' + value.city + '</option>');
                    });
                      $('.selectpicker').selectpicker("refresh");

                }
            });
        });
        $('#address_id').on("change",function () {
            var countryId = $(this).val();
            console.log('getting address...');
            $.ajax({
                url: '../sales/getshipping/' + countryId,
                type: 'GET',
                success: function (data) {
                    
                        $('#shipping-cost').text(data);
                        $('#shipping-cost-val').val(data);
                    

                }
            });
        });
        function updateshipping(customer){
            $.ajax({
                url: '../sales/getshippingbycustomer/' + customer,
                type: 'GET',
                success: function (data) {
                    
                        $('#shipping-cost').text(data);
                        $('#shipping-cost-val').val(data);
                    

                }
            });
        }
        $('#customer_id').on("change",function () {
            var customer = $(this).val();
            
            $('#address_id').empty();
            updateshipping(customer);
            $('#customer').val(customer);
            $.ajax({
                url: '../sales/getaddress/' + customer,
                type: 'GET',
                success: function (data) {
                    

                    $.each(data, function (key, value) {
                        $('#address_id').append(value);
                    });
                    
                      $('.selectpicker').selectpicker("refresh");

                }
            });
        });
    
  $(document).ready(function () {
    // Initialize Select2
    $('#customer_id').select2({
      placeholder: 'Select a customer',
      ajax: {
        url: '../customer/get-customer',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            id: params.term, // Pass the entered ID as a parameter
          };
        },
        processResults: function (data) {
          return {
            results: data,
          };
        },
        cache: true
      },
      minimumInputLength: 5, // Set to 0 if you want to show results even without typing
      templateResult: function (data) {
        return data.text; // Display the 'text' property in the dropdown
      },
      templateSelection: function (data) {
        return data.text; // Display the 'text' property in the selected option
      }
    });
  });



</script>
@endpush
