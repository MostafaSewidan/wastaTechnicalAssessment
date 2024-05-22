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
                        <h4>{{trans('file.General Setting')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'setting.generalStore', 'files' => true, 'method' => 'post']) !!}
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.System Title')}} *</label>
                                        <input type="text" name="site_title" class="form-control" value="@if($lims_general_setting_data){{$lims_general_setting_data->site_title}}@endif" required />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.System Logo')}} *</label>
                                        <input type="file" name="site_logo" class="form-control" value=""/>
                                    </div>
                                    @if($errors->has('site_logo'))
                                   <span>
                                       <strong>{{ $errors->first('site_logo') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        @if($lims_general_setting_data->is_rtl)
                                        <input type="checkbox" name="is_rtl" value="1" checked>
                                        @else
                                        <input type="checkbox" name="is_rtl" value="1" />
                                        @endif
                                        &nbsp;
                                        <label>{{trans('file.RTL Layout')}}</label>

                                    </div>
                                </div>
                                @if(config('database.connections.saleprosaas_landlord'))
                                    <div class="col-md-4 mt-4">
                                        <div class="form-group">
                                            @if($lims_general_setting_data->is_zatca)
                                            <input type="checkbox" name="is_zatca" value="1" checked>
                                            @else
                                            <input type="checkbox" name="is_zatca" value="1" />
                                            @endif
                                            &nbsp;
                                            <label>{{trans('file.ZATCA QrCode')}}</label>

                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Company Name')}}</label>
                                        <input type="text" name="company_name" class="form-control" value="@if($lims_general_setting_data){{$lims_general_setting_data->company_name}}@endif" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.VAT Registration Number')}}</label>
                                        <input type="text" name="vat_registration_number" class="form-control" value="@if($lims_general_setting_data){{$lims_general_setting_data->vat_registration_number}}@endif" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Time Zone')}}</label>
                                        @if($lims_general_setting_data)
                                        <input type="hidden" name="timezone_hidden" value="{{env('APP_TIMEZONE')}}">
                                        @endif
                                        <select name="timezone" class="selectpicker form-control" data-live-search="true" title="Select TimeZone...">
                                            @foreach($zones_array as $zone)
                                            <option value="{{$zone['zone']}}">{{$zone['diff_from_GMT'] . ' - ' . $zone['zone']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Currency')}} *</label>
                                        <select name="currency" class="form-control" required>
                                            @foreach($lims_currency_list as $key => $currency)
                                                @if($lims_general_setting_data->currency == $currency->id)
                                                    <option value="{{$currency->id}}" selected>{{$currency->name}}</option>
                                                @else
                                                    <option value="{{$currency->id}}">{{$currency->name}}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Currency Position')}} *</label><br>
                                        @if($lims_general_setting_data->currency_position == 'prefix')
                                        <label class="radio-inline">
                                            <input type="radio" name="currency_position" value="prefix" checked> {{trans('file.Prefix')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="currency_position" value="suffix"> {{trans('file.Suffix')}}
                                        </label>
                                        @else
                                        <label class="radio-inline">
                                            <input type="radio" name="currency_position" value="prefix"> {{trans('file.Prefix')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="currency_position" value="suffix" checked> {{trans('file.Suffix')}}
                                        </label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Digits after deciaml point')}}*</label>
                                        <input class="form-control" type="number" name="decimal" value="@if($lims_general_setting_data){{$lims_general_setting_data->decimal}}@endif" max="6" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4 d-none">
                                    <div class="form-group">
                                        <label>{{trans('file.Theme')}} *</label>
                                        <div class="row ml-1">
                                            <div class="col-md-3 theme-option" data-color="default.css" style="background: #7c5cc4; min-height: 40px; max-width: 50px;" title="Purple"></div>&nbsp;&nbsp;
                                            <div class="col-md-3 theme-option" data-color="green.css" style="background: #1abc9c; min-height: 40px;max-width: 50px;" title="Green"></div>&nbsp;&nbsp;
                                            <div class="col-md-3 theme-option" data-color="blue.css" style="background: #3498db; min-height: 40px;max-width: 50px;" title="Blue"></div>&nbsp;&nbsp;
                                            <div class="col-md-3 theme-option" data-color="dark.css" style="background: #34495e; min-height: 40px;max-width: 50px;" title="Dark"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Sale and Quotation without stock')}} *</label><br>
                                        @if($lims_general_setting_data->without_stock == 'yes')
                                        <label class="radio-inline">
                                            <input type="radio" name="without_stock" value="yes" checked> {{trans('file.Yes')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="without_stock" value="no"> {{trans('file.No')}}
                                        </label>
                                        @else
                                        <label class="radio-inline">
                                            <input type="radio" name="without_stock" value="yes"> {{trans('file.Yes')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="without_stock" value="no" checked> {{trans('file.No')}}
                                        </label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Staff Access')}} *</label>
                                        @if($lims_general_setting_data)
                                        <input type="hidden" name="staff_access_hidden" value="{{$lims_general_setting_data->staff_access}}">
                                        @endif
                                        <select name="staff_access" class="selectpicker form-control">
                                            <option value="all"> {{trans('file.All Records')}}</option>
                                            <option value="own"> {{trans('file.Own Records')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Invoice Format')}} *</label>
                                        @if($lims_general_setting_data)
                                        <input type="hidden" name="invoice_format_hidden" value="{{$lims_general_setting_data->invoice_format}}">
                                        @endif
                                        <select name="invoice_format" class="selectpicker form-control" required>
                                            <option value="standard">Standard</option>
                                            <option value="gst">Indian GST</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="state" class="col-md-4 d-none">
                                    <div class="form-group">
                                        <label>{{trans('file.State')}} *</label>
                                        @if($lims_general_setting_data)
                                        <input type="hidden" name="state_hidden" value="{{$lims_general_setting_data->state}}">
                                        @endif
                                        <select name="state" class="selectpicker form-control">
                                            <option value="1">Home State</option>
                                            <option value="2">Buyer State</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Date Format')}} *</label>
                                        @if($lims_general_setting_data)
                                        <input type="hidden" name="date_format_hidden" value="{{$lims_general_setting_data->date_format}}">
                                        @endif
                                        <select name="date_format" class="selectpicker form-control">
                                            <option value="d-m-Y"> dd-mm-yyy</option>
                                            <option value="d/m/Y"> dd/mm/yyy</option>
                                            <option value="d.m.Y"> dd.mm.yyy</option>
                                            <option value="m-d-Y"> mm-dd-yyy</option>
                                            <option value="m/d/Y"> mm/dd/yyy</option>
                                            <option value="m.d.Y"> mm.dd.yyy</option>
                                            <option value="Y-m-d"> yyy-mm-dd</option>
                                            <option value="Y/m/d"> yyy/mm/dd</option>
                                            <option value="Y.m.d"> yyy.mm.dd</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Developed By')}}</label>
                                        <input type="text" name="developed_by" class="form-control" value="{{$lims_general_setting_data->developed_by}}">
                                    </div>
                                </div>
                                @if(config('database.connections.saleprosaas_landlord'))
                                    <br>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Subscription Type')}}</label>
                                            <p>{{$lims_general_setting_data->subscription_type}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Package Name')}}</label>
                                            <p id="package-name"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Monthly Fee')}}</label>
                                            <p id="monthly-fee"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Yearly Fee')}}</label>
                                            <p id="yearly-fee"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{trans('file.Number of Warehouses')}}</label>
                                            <p id="number-of-warehouse"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Number of Products')}}</label>
                                            <p id="number-of-product"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Number of Invoices')}}</label>
                                            <p id="number-of-invoice"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{trans('file.Number of User Account')}}</label>
                                            <p id="number-of-user-account"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Number of Employees')}}</label>
                                            <p id="number-of-employee"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Subscription Ends at')}}</label>
                                            <p>{{date($lims_general_setting_data->date_format, strtotime($lims_general_setting_data->expiry_date))}}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="form-group">
                                <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary">
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
    $("ul#setting #general-setting-menu").addClass("active");

    $("select[name=invoice_format]").on("change", function (argument) {
        if($(this).val() == 'standard') {
            $("#state").addClass('d-none');
            $("input[name=state]").prop("required", false);
        }
        else if($(this).val() == 'gst') {
            $("#state").removeClass('d-none');
            $("input[name=state]").prop("required", true);
        }
    })
    if($("input[name='timezone_hidden']").val()){
        $('select[name=timezone]').val($("input[name='timezone_hidden']").val());
        $('select[name=staff_access]').val($("input[name='staff_access_hidden']").val());
        $('select[name=date_format]').val($("input[name='date_format_hidden']").val());
        $('select[name=invoice_format]').val($("input[name='invoice_format_hidden']").val());
        if($("input[name='invoice_format_hidden']").val() == 'gst') {
            $('select[name=state]').val($("input[name='state_hidden']").val());
            $("#state").removeClass('d-none');
        }
        $('.selectpicker').selectpicker('refresh');
    }

    $('.theme-option').on('click', function() {
        $.get('general_setting/change-theme/' + $(this).data('color'), function(data) {
        });
        var style_link= $('#custom-style').attr('href').replace(/([^-]*)$/, $(this).data('color') );
        $('#custom-style').attr('href', style_link);
    });

    @if(config('database.connections.saleprosaas_landlord'))
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", $lims_general_setting_data->package_id)}}',
            success: function(data) {
                $("#package-name").text(data['name']);
                $("#monthly-fee").text(data['monthly_fee']);
                $("#yearly-fee").text(data['yearly_fee']);
                $("#package-name").text(data['name']);

                if(data['number_of_warehouse'])
                    $("#number-of-warehouse").text(data['number_of_warehouse']);
                else
                    $("#number-of-warehouse").text('Unlimited');

                if(data['number_of_product'])
                    $("#number-of-product").text(data['number_of_product']);
                else
                    $("#number-of-product").text('Unlimited');

                if(data['number_of_invoice'])
                    $("#number-of-invoice").text(data['number_of_invoice']);
                else
                    $("#number-of-invoice").text('Unlimited');

                if(data['number_of_user_account'])
                    $("#number-of-user-account").text(data['number_of_user_account']);
                else
                    $("#number-of-user-account").text('Unlimited');

                if(data['number_of_employee'])
                    $("#number-of-employee").text(data['number_of_employee']);
                else
                    $("#number-of-employee").text('Unlimited');
            }
        });
    @endif
</script>
@endpush
