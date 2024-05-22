@extends('backend.layout.main') @section('content')

@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('file.Update Discount')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => ['discounts.update', $lims_discount_data->id], 'method' => 'put']) !!}
                            <div class="row">
                                <div class="col-md-3 form-group">
                                	<label>{{trans('file.name')}} *</label>
                                    <input type="text" name="name" value="{{$lims_discount_data->name}}" required class="form-control">
                                </div>
                                <div class="col-md-3 form-group">
                                	<label>{{trans('file.Discount Plan')}} *</label>
                                    <select required name="discount_plan_id[]" class="selectpicker form-control discount-plan-id" data-live-search="true" data-live-search-style="begins" title="Select discount plan..." multiple>
                                    	@foreach($lims_discount_plan_list as $discount_plan)
                                        	<option value="{{$discount_plan->id}}">{{$discount_plan->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                	<label>{{trans('file.Applicable For')}} *</label>
                                    <select required name="applicable_for" class="form-control">
                                        @if($lims_discount_data->applicable_for == 'All')
                                            <option selected value="All">{{trans('file.All Products')}}</option>
                                            <option value="Specific">{{trans('file.Specific Products')}}</option>
                                        @else
                                            <option value="All">{{trans('file.All Products')}}</option>
                                            <option selected value="Specific">{{trans('file.Specific Products')}}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-3 mt-4">
                                    @if($lims_discount_data->is_active)
                                        <input type="checkbox" name="is_active" value="1" checked>
                                    @else
                                        <input type="checkbox" name="is_active" value="1">
                                    @endif
                                    <label>{{trans('file.Active')}}</label>
                                </div>
                                <div class="col-md-9 form-group product-selection">
                                	<label>{{trans('file.Select Product')}} *</label>
                                	<input type="text" name="product_code" id="product-code" class="form-control" placeholder="{{trans('file.Type product code seperated by comma')}}">
                                </div>
                                <div class="col-md-9 form-group product-selection">
                                	<div class="table-responsive ml-2">
                                        <table id="product-table" class="table">
                                            <thead>
                                                <tr>
                                                    <th><i class="dripicons-view-apps"></i></th>
                                                    <th>{{trans('file.name')}}</th>
                                                    <th>{{trans('file.Code')}}</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @if($lims_discount_data->applicable_for == 'Specific')
                                                <?php $product_ids = explode(",", $lims_discount_data->product_list); ?>
                                                @foreach($product_ids as $key => $product_id)
                                                <?php $product_data = \App\Models\Product::select('id', 'name', 'code')->find($product_id); ?>
                                                    <tr>
                                                        <td><input type="hidden" name="product_list[]" value="{{$product_data->id}}" />{{$key+1}}</td>
                                                        <td>{{$product_data->name}}</td>
                                                        <td>{{$product_data->code}}</td>
                                                        <td><button type="button" class="pbtnDel btn btn-sm btn-danger">X</button></td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Valid From')}} *</label>
                                    <input type="text" name="valid_from" value="{{date($general_setting->date_format, strtotime($lims_discount_data->valid_from))}}" required class="form-control date">
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Valid Till')}} *</label>
                                    <input type="text" name="valid_till" value="{{date($general_setting->date_format, strtotime($lims_discount_data->valid_till))}}" required class="form-control date">
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Discount Type')}} *</label>
                                    <select name="type" class="form-control">
                                        @if($lims_discount_data->type == 'percentage')
                                        	<option value="percentage" selected>Percentage (%)</option>
                                        	<option value="flat">Flat</option>
                                        @else
                                            <option value="percentage">Percentage (%)</option>
                                            <option value="flat" selected>Flat</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Value')}} *</label>
                                    <input type="number" name="value" value="{{$lims_discount_data->value}}" required class="form-control">
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Minimum Qty')}} *</label>
                                    <input type="number" name="minimum_qty" value="{{$lims_discount_data->minimum_qty}}" required class="form-control">
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Maximum Qty')}} *</label>
                                    <input type="number" name="maximum_qty" value="{{$lims_discount_data->maximum_qty}}" required class="form-control">
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Valid on the following days')}}</label>
                                	<ul style="list-style-type: none; margin-left: -30px;">
                                		<li><input type="checkbox" class="Mon" name="days[]" value="Mon">&nbsp; Monday</li>
                                		<li><input type="checkbox" class="Tue" name="days[]" value="Tue">&nbsp; Tuesday</li>
                                		<li><input type="checkbox" class="Wed" name="days[]" value="Wed">&nbsp; Wednesday</li>
                                		<li><input type="checkbox" class="Thu" name="days[]" value="Thu">&nbsp; Thursday</li>
                                		<li><input type="checkbox" class="Fri" name="days[]" value="Fri">&nbsp; Friday</li>
                                		<li><input type="checkbox" class="Sat" name="days[]" value="Sat">&nbsp; Saturday</li>
                                		<li><input type="checkbox" class="Sun" name="days[]" value="Sun">&nbsp; Sunday</li>
                                	</ul>
                                </div>
                                <div class="col-md-12 mt-2">
                                    <button type="submit" class="btn btn-primary">{{trans('file.submit')}}</button>
                                </div>
                            </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #discount-menu").addClass("active");

    var discount_plan_ids = <?php echo json_encode($discount_plan_ids); ?>;
    var applicable_for = <?php echo json_encode($lims_discount_data->applicable_for); ?>;
    var days = <?php echo json_encode(explode(",", $lims_discount_data->days)); ?>;
    for(i = 0; i < days.length; i++) {
        $("."+days[i]).prop('checked', true);
    }

    if(applicable_for == 'All')
        $(".product-selection").hide();
    $(".discount-plan-id").val(discount_plan_ids);

    $("input[name='product_code']").on("input", function () {
        if($(this).val().indexOf(',') > -1){
            var code = $(this).val().slice(0, -1);
            $.get('../product-search/' + code, function(data) {
		        var newRow = $("<tr>");
	            var cols = '';
	            var rowindex = $("table#product-table tbody tr:last").index();
	            cols += '<td><input type="hidden" name="product_list[]" value="'+ data[0] +'" />'+(rowindex+2)+'</td>';
	            cols += '<td>'+ data[1] +'</td>';
	            cols += '<td>'+ data[2] +'</td>';
	            cols += '<td><button type="button" class="pbtnDel btn btn-sm btn-danger">X</button></td>';
	            newRow.append(cols);
	            $("table#product-table tbody").append(newRow);
		    });
            $(this).val('');
        }
    });

    //Delete product
    $("table#product-table tbody").on("click", ".pbtnDel", function(event) {
        $(this).closest("tr").remove();
    });

    $("select[name=applicable_for]").on("change", function(){
    	if($(this).val() == 'All') {
    		$(".product-selection").hide(300);
    	}
    	else {
    		$(".product-selection").show(300);
    	}
    });

    $('.date').datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });
</script>
@endpush
