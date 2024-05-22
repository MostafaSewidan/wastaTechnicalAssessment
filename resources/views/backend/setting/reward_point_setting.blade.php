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
                        <h4>{{trans('file.Reward Point Setting')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'setting.rewardPointStore', 'files' => true, 'method' => 'post']) !!}
                            <div class="row">
                                <div class="col-md-3 mt-3">
                                    <div class="form-group">
                                        @if($lims_reward_point_setting_data->is_active)
                                        <input type="checkbox" name="is_active" value="1" checked>
                                        @else
                                        <input type="checkbox" name="is_active" value="1">
                                        @endif &nbsp;
                                        <label>{{trans('file.Active reward point')}}</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Sold amount per point')}} *</label> <i class="dripicons-question" data-toggle="tooltip" title="{{trans('file.This means how much point customer will get according to sold amount. For example, if you put 100 then for every 100 dollar spent customer will get one point as reward.')}}"></i>
                                        <input type="number" name="per_point_amount" class="form-control" value="{{$lims_reward_point_setting_data->per_point_amount}}" required />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Minumum sold amount to get point')}} * <i class="dripicons-question" data-toggle="tooltip" title="{{trans('file.For example, if you put 100 then customer will only get point after spending 100 dollar or more.')}}"></i></label>
                                        <input type="number" name="minimum_amount" class="form-control" value="{{$lims_reward_point_setting_data->minimum_amount}}" required />
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>{{trans('file.Point Expiry Duration')}}</label>
                                        <input type="number" name="duration" class="form-control" value="{{$lims_reward_point_setting_data->duration}}" />
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>{{trans('file.Duration Type')}}</label>
                                        <select name="type" class="form-control">
                                            @if($lims_reward_point_setting_data->type == 'Year')
                                                <option selected value="Year">Years</option>
                                                <option value="Month">Months</option>
                                            @else
                                                <option value="Year">Years</option>
                                                <option selected value="Month">Months</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12 form-group">
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
    $("ul#setting #reward-point-setting-menu").addClass("active");

    $('[data-toggle="tooltip"]').tooltip();

</script>
@endpush
