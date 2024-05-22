<?php $general_setting = DB::table('general_settings')->find(1); ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{$general_setting->site_title}}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    <link rel="icon" type="image/png" href="{{url('public/logo', $general_setting->site_logo)}}" />
    @if(!config('database.connections.saleprosaas_landlord'))
    <link rel="icon" type="image/png" href="{{url('logo', $general_setting->site_logo)}}" />
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
    <!-- login stylesheet-->
    <link rel="stylesheet" href="<?php echo asset('css/auth.css') ?>" id="theme-stylesheet" type="text/css">
    <!-- Google fonts - Roboto -->
    <link rel="preload" href="https://fonts.googleapis.com/css?family=Nunito:400,500,700" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css?family=Nunito:400,500,700" rel="stylesheet"></noscript>
    <script type="text/javascript" src="<?php echo asset('vendor/jquery/jquery.min.js') ?>"></script>
    @else
    <link rel="icon" type="image/png" href="{{url('../../logo', $general_setting->site_logo)}}" />
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo asset('../../vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
    <!-- login stylesheet-->
    <link rel="stylesheet" href="<?php echo asset('../../css/auth.css') ?>" id="theme-stylesheet" type="text/css">
    <!-- Google fonts - Roboto -->
    <link rel="preload" href="https://fonts.googleapis.com/css?family=Nunito:400,500,700" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css?family=Nunito:400,500,700" rel="stylesheet"></noscript>
    <script type="text/javascript" src="<?php echo asset('../../vendor/jquery/jquery.min.js') ?>"></script>
    @endif
  </head>
  <body>
    <div class="page login-page">
      <div class="container">
        <div class="form-outer text-center d-flex align-items-center">
          <div class="form-inner">
            <div class="logo"><span>{{$general_setting->site_title}}</span></div>
            <form method="POST" action="{{ route('register') }}">
                @csrf
              <div class="form-group-material">
                <input id="register-username" type="text" name="name" required class="input-material">
                <label for="register-username" class="label-material">{{trans('file.UserName')}} *</label>
                @if ($errors->has('name'))
                    <p>
                        <strong>{{ $errors->first('name') }}</strong>
                    </p>
                @endif
              </div>
              <div class="form-group-material">
                <input id="register-email" type="email" name="email" required class="input-material">
                <label for="register-email" class="label-material">{{trans('file.Email')}} *</label>
                @if ($errors->has('email'))
                    <p>
                        <strong>{{ $errors->first('email') }}</strong>
                    </p>
                @endif
              </div>
              <div class="form-group-material">
                <input id="register-phone" type="text" name="phone_number" required class="input-material">
                <label for="register-phone" class="label-material">{{trans('file.Phone Number')}} *</label>
              </div>
              <div class="form-group-material">
                <input id="register-company" type="text" name="company_name" class="input-material">
                <label for="register-company" class="label-material">{{trans('file.Company Name')}}</label>
              </div>
              <div class="form-group-material">
                <select required name="role_id" id="role-id" class="form-control">
                  <option value="">Select Role*</option>
                  @foreach($lims_role_list as $role)
                      <option value="{{$role->id}}">{{$role->name}}</option>
                  @endforeach
                </select>
              </div>
              <div id="customer-section">
                  <div class="form-group-material">
                    <input id="customer-name" type="text" name="customer_name" class="input-material customer-field">
                    <label for="customer-name" class="label-material">{{trans('file.name')}} *</label>
                  </div>
                  <div class="form-group-material">
                    <select name="customer_group_id" class="form-control customer-field">
                      <option value="">Select customer group*</option>
                      @foreach($lims_customer_group_list as $customer_group)
                          <option value="{{$customer_group->id}}">{{$customer_group->name}}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-group-material">
                    <input id="customer-tax-number" type="text" name="tax_no" class="input-material">
                    <label for="customer-tax-number" class="label-material">{{trans('file.Tax Number')}}</label>
                  </div>
                  <div class="form-group-material">
                    <input id="customer-address" type="text" name="address" class="input-material customer-field">
                    <label for="customer-address" class="label-material">{{trans('file.Address')}} *</label>
                  </div>
                  <div class="form-group-material">
                    <input id="customer-city" type="text" name="city" class="input-material customer-field">
                    <label for="customer-city" class="label-material">{{trans('file.City')}} *</label>
                  </div>
                  <div class="form-group-material">
                    <input id="customer-state" type="text" name="state" class="input-material">
                    <label for="customer-state" class="label-material">{{trans('file.State')}}</label>
                  </div>
                  <div class="form-group-material">
                    <input id="customer-postal" type="text" name="postal_code" class="input-material">
                    <label for="customer-postal" class="label-material">{{trans('file.Postal Code')}}</label>
                  </div>
                  <div class="form-group-material">
                    <input id="customer-country" type="text" name="country" class="input-material">
                    <label for="customer-country" class="label-material">{{trans('file.Country')}}</label>
                  </div>
              </div>
              <div class="form-group-material" id="biller-id">
                <select name="biller_id" class="form-control">
                  <option value="">Select Biller*</option>
                  @foreach($lims_biller_list as $biller)
                      <option value="{{$biller->id}}">{{$biller->name}} ({{$biller->phone_number}})</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group-material" id="warehouse-id">
                <select name="warehouse_id" class="form-control">
                  <option value="">Select Warehouse*</option>
                  @foreach($lims_warehouse_list as $warehouse)
                      <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group-material">
                <input id="password" type="password" class="input-material" name="password" required>
                <label for="passowrd" class="label-material">{{trans('file.Password')}} *</label>
                @if ($errors->has('password'))
                    <p>
                        <strong>{{ $errors->first('password') }}</strong>
                    </p>
                @endif
              </div>
              <div class="form-group-material">
                <input id="password-confirm" type="password" name="password_confirmation" required class="input-material">
                <label for="password-confirm" class="label-material">{{trans('file.Confirm Password')}} *</label>
              </div>
              <input id="register" type="submit" value="Register" class="btn btn-primary">
            </form><p>{{trans('file.Already have an account')}}? </p><a href="{{url('login')}}" class="signup">{{trans('file.LogIn')}}</a>
          </div>
        </div>
      </div>
    </div>
    <script type="text/javascript">

      @if(config('database.connections.saleprosaas_landlord'))
        numberOfUserAccount = <?php echo json_encode($numberOfUserAccount)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", $general_setting->package_id)}}',
            success: function(data) {
                if(data['number_of_user_account'] > 0 && data['number_of_user_account'] <= numberOfUserAccount) {
                    localStorage.setItem("message", "You don't have permission to create another user account as you already exceed the limit! Subscribe to another package if you wants more!");
                    location.href = "{{route('user.index')}}";
                }
            }
        });
    @endif
      // ------------------------------------------------------- //
    // Material Inputs
    // ------------------------------------------------------ //

        var materialInputs = $('input.input-material');

        // activate labels for prefilled values
        materialInputs.filter(function() { return $(this).val() !== ""; }).siblings('.label-material').addClass('active');

        // move label on focus
        materialInputs.on('focus', function () {
            $(this).siblings('.label-material').addClass('active');
        });

        // remove/keep label on blur
        materialInputs.on('blur', function () {
            $(this).siblings('.label-material').removeClass('active');

            if ($(this).val() !== '') {
                $(this).siblings('.label-material').addClass('active');
            } else {
                $(this).siblings('.label-material').removeClass('active');
            }
        });


        $("#biller-id").hide();
        $("#warehouse-id").hide();
        $("#customer-section").hide();

        $("#role-id").on("change", function () {
            if($(this).val() == '5') {
              $("#customer-section").show(300);
              $(".customer-field").prop('required', true);
              $("#biller-id").hide(300);
              $("#warehouse-id").hide(300);
              $("select[name='biller_id']").prop('required', false);
              $("select[name='warehouse_id']").prop('required', false);
            }
            else if($(this).val() > 2) {
              $("#customer-section").hide(300);
              $("#biller-id").show(300);
              $("#warehouse-id").show(300);
              $("select[name='biller_id']").prop('required', true);
              $("select[name='warehouse_id']").prop('required', true);
              $(".customer-field").prop('required', false);
            }
            else {
              $("#biller-id").hide(300);
              $("#warehouse-id").hide(300);
              $("#customer-section").hide(300);
              $("select[name='biller_id']").prop('required', false);
              $("select[name='warehouse_id']").prop('required', false);
              $(".customer-field").prop('required', false);
            }
        });
    </script>
  </body>
</html>
