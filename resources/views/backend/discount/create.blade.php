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
                        <h4>{{trans('file.Create Discount')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'discounts.store', 'method' => 'post']) !!}
                            <div class="row">
                                <div class="col-md-3 form-group">
                                	<label>{{trans('file.name')}} *</label>
                                    <input type="text" name="name" required class="form-control">
                                </div>
                                <div class="col-md-3 form-group">
                                	<label>{{trans('file.Discount Plan')}} *</label>
                                    <select required name="discount_plan_id[]" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select discount plan..." multiple>
                                    	@foreach($lims_discount_plan_list as $discount_plan)
                                        	<option value="{{$discount_plan->id}}">{{$discount_plan->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                	<label>{{trans('file.Applicable For')}} *</label>
                                    <select required name="applicable_for" class="form-control">
                                        <option value="All">{{trans('file.All Products')}}</option>
                                        <option value="Specific">{{trans('file.Specific Products')}}</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mt-4">
                                    <input type="checkbox" name="is_active" value="1" checked>
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
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Valid From')}} *</label>
                                    <input type="text" name="valid_from" required class="form-control date">
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Valid Till')}} *</label>
                                    <input type="text" name="valid_till" required class="form-control date">
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Discount Type')}} *</label>
                                    <select name="type" class="form-control">
                                    	<option value="percentage">Percentage (%)</option>
                                    	<option value="flat">Flat</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Value')}} *</label>
                                    <input type="number" name="value" required class="form-control">
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Minimum Qty')}} *</label>
                                    <input type="number" name="minimum_qty" required class="form-control">
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Maximum Qty')}} *</label>
                                    <input type="number" name="maximum_qty" required class="form-control">
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{trans('file.Valid on the following days')}}</label>
                                	<ul style="list-style-type: none; margin-left: -30px;">
                                		<li><input type="checkbox" name="days[]" value="Mon" checked>&nbsp; Monday</li>
                                		<li><input type="checkbox" name="days[]" value="Tue" checked>&nbsp; Tuesday</li>
                                		<li><input type="checkbox" name="days[]" value="Wed" checked>&nbsp; Wednesday</li>
                                		<li><input type="checkbox" name="days[]" value="Thu" checked>&nbsp; Thursday</li>
                                		<li><input type="checkbox" name="days[]" value="Fri" checked>&nbsp; Friday</li>
                                		<li><input type="checkbox" name="days[]" value="Sat" checked>&nbsp; Saturday</li>
                                		<li><input type="checkbox" name="days[]" value="Sun" checked>&nbsp; Sunday</li>
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
    $(".product-selection").hide();

    $("input[name='product_code']").on("input", function () {
        if($(this).val().indexOf(',') > -1){
            var code = $(this).val().slice(0, -1);
            $.get('product-search/' + code, function(data) {
		        var newRow = $("<tr>");
	            var cols = '';
	            var rowindex = $("table#product-table tbody tr:last").index();
	            console.log(rowindex);
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
