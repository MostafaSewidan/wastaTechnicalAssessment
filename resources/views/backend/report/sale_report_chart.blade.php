@extends('backend.layout.main') @section('content')
<section class="forms">
    <div class="container-fluid">
    	<div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{trans('file.Sale Report Chart')}}</h3>
            </div>
            {!! Form::open(['route' => 'report.saleChart', 'method' => 'post']) !!}
            <div class="row ml-2">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{trans('file.Choose Your Date')}}</strong></label>
                        <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                        <input type="hidden" name="start_date" value="{{$start_date}}" />
                        <input type="hidden" name="end_date" value="{{$end_date}}" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="d-tc mt-2"><strong>{{trans('file.Choose Warehouse')}}</strong> &nbsp;</label>
                        <input type="hidden" name="warehouse_id_hidden" value="{{$warehouse_id}}" />
                        <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" >
                            <option value="0">{{trans('file.All Warehouse')}}</option>
                            @foreach($lims_warehouse_list as $warehouse)
                            <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><strong>{{trans('file.Time Period')}}</strong></label>
                        <select class="form-control" name="time_period">
                            @if($time_period == 'weekly')
                                <option value="weekly" selected>Weekly</option>
                                <option value="monthly">Monthly</option>
                            @else
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>{{trans('file.product_list')}}</strong></label>
                        <input type="text" name="product_list" class="form-control" placeholder="Type product code seperated by comma">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">{{trans('file.submit')}}</button>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
        <?php 
            $color = '#733686';
            $color_rgba = 'rgba(115, 54, 134, 0.8)';
        ?>
        <div class="col-md-12">
            <div class="card-body">
                <canvas id="sale-report-chart" data-color="{{$color}}" data-color_rgba="{{$color_rgba}}" data-soldqty="{{json_encode($sold_qty)}}" data-datepoints="{{json_encode($date_points)}}" data-label1="{{trans('file.Sold Qty')}}"></canvas>
            </div>
        </div>
    </div>
</section>

@endsection
@push('scripts')
<script type="text/javascript">

	$("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #sale-report-chart-menu").addClass("active");

	$('#warehouse_id').val($('input[name="warehouse_id_hidden"]').val());
	$('.selectpicker').selectpicker('refresh');

    $(".daterangepicker-field").daterangepicker({
      callback: function(startDate, endDate, period){
        var start_date = startDate.format('YYYY-MM-DD');
        var end_date = endDate.format('YYYY-MM-DD');
        var title = start_date + ' To ' + end_date;
        $(this).val(title);
        $('input[name="start_date"]').val(start_date);
        $('input[name="end_date"]').val(end_date);
      }
    });
</script>
@endpush
