@extends('backend.layout.main')
@section('content')

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
                        <h4>{{trans('file.Create Discount Plan')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'discount-plans.store', 'method' => 'post']) !!}
                            <div class="row">
                                <div class="col-md-4">
                                    <label>{{trans('file.name')}} *</label>
                                    <input type="text" name="name" required class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label>{{trans('file.warehouse')}} *</label>
                                    <select required name="warehouse_id[]" class="selectpicker form-control" data-live-search="true"  title="Select warehouse..." multiple>
                                        @foreach($lims_warehouse_list as $warehouse)
                                            <option value="{{$warehouse->id}}">{{$warehouse->name . ' (' . $warehouse->phone_number . ')'}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mt-4">
                                    <div class="form-group">
                                        <input type="checkbox" name="is_active" value="1" checked>
                                        <label>{{trans('file.Active')}}</label>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3">
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
    $("ul#setting #discount-plan-menu").addClass("active");

    $('[data-toggle="tooltip"]').tooltip();

</script>
@endpush
