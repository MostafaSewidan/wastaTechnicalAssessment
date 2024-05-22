<div class="tab-pane fade" id="list-api-settings" role="tabpanel"
                                aria-labelledby="list-api-settings-list">

        <form action="{{route('woocommerce.store')}}" method="POST">
            @csrf
            <input type="hidden" name="api_settings" value="1">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="inputEmail4"><b>{{trans('file.WooCommerce App URL')}}</b></label>
                    <input type="text" @if($woocommerce_setting) value="{{$woocommerce_setting->woocomerce_app_url}}" @endif name="woocomerce_app_url" class="form-control" id="inputEmail4"
                        placeholder="WooCommerce App URL">
                </div>
                <div class="form-group col-md-4">
                    <label for="inputPassword4"><b>{{trans('file.WooCommerce Consumer Key')}}</b></label>
                    <input type="text"  name="woocomerce_consumer_key"@if($woocommerce_setting) value="{{$woocommerce_setting->woocomerce_consumer_key}}" @endif class="form-control" id="inputPassword4"
                        placeholder="WooCommerce Consumer Key:">
                </div>
                <div class="form-group col-md-4">
                    <label for="inputPassword4"><b>{{trans('file.WooCommerce Consumer Secret')}}</b></label>
                    <input type="password"  name="woocomerce_consumer_secret"@if($woocommerce_setting) value="{{$woocommerce_setting->woocomerce_consumer_secret}}" @endif class="form-control" id="inputPassword4"
                        placeholder="WooCommerce Consumer Secret">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{trans('file.Submit')}}</button>
        </form>
    </div>
