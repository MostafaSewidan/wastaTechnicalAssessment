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
                        <h4>{{trans('file.Mail Setting')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'setting.mailStore', 'files' => true, 'method' => 'post']) !!}
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Mail Driver')}} *</label>
                                <input type="text" name="driver" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->driver}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Mail Host')}} *</label>
                                <input type="text" name="host" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->host}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Mail Port')}} *</label>
                                <input type="text" name="port" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->port}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Mail Address')}} *</label>
                                <input type="text" name="from_address" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->from_address}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Mail From Name')}} *</label>
                                <input type="text" name="from_name" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->from_name}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.UserName')}} *</label>
                                <input type="text" name="username" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->username}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Password')}} *</label>
                                <input type="password" name="password" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->password}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Encryption')}} *</label>
                                <input type="text" name="encryption" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->encryption}}@endif" required />
                            </div>
                            <div class="col-md-12 form-group">
                                <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary">
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
    $("ul#setting #mail-setting-menu").addClass("active");
</script>
@endpush
