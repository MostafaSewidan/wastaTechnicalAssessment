<div class="tab-pane fade" id="list-product-sync-settings" role="tabpanel" aria-labelledby="list-product-sync-settings-list">
    <form action="{{route('woocommerce.store')}}" method="POST">
        @csrf
        <input type="hidden" name="product_sync_settings" value="1">

        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="inputEmail4"><b>{{trans('file.Default Tax Class')}}</b></label>
                <input type="text" name="default_tax_class" class="form-control" id="inputEmail4" value="{{$woocommerce_setting->default_tax_class ?? null }}"
                    placeholder="Default Tax Class">
            </div>
            <div class="form-group col-md-4">
                <label for="inputPassword4"><b>{{trans('file.Sync Product Price')}}</b></label>
                <select name="product_tax_type"  class="form-control">
                    <option value=''>--Select--</option>
                    <option value="exc" @if($woocommerce_setting && $woocommerce_setting->product_tax_type=='exc') selected @endif>Excluding Tax</option>
                    <option value="inc" @if($woocommerce_setting && $woocommerce_setting->product_tax_type=='inc') selected @endif>Including Tax</option>
                </select>
            </div>
        </div>

        <br>
        <h4><b>{{trans('file.WooCommerce Settings')}}</b></h4>
        <br>
        <div class="row">
            <div class="form-group col-md-3">
                <label for="inputEmail4"><b>{{trans('file.Manage Stock')}}</b></label>
                <select class="form-control" name='manage_stock'>
                    <option value=''>--Select--</option>
                    <option value="true" @isset($woocommerce_setting->manage_stock) {{ $woocommerce_setting->manage_stock=='true' ? 'selected':'' }} @endisset>True</option>
                    <option value="false" @isset($woocommerce_setting->manage_stock) {{ $woocommerce_setting->manage_stock=='false' ? 'selected':'' }} @endisset>False</option>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label for="inputEmail4"><b>{{trans('file.Stock Status')}}</b></label>
                <select class="form-control" name='stock_status'>
                    <option value=''>--Select--</option>
                    <option value="instock" @isset($woocommerce_setting->stock_status) {{ $woocommerce_setting->stock_status=='instock' ? 'selected':'' }} @endisset>In stock</option>
                    <option value="outofstock" @isset($woocommerce_setting->stock_status) {{ $woocommerce_setting->stock_status=='outofstock' ? 'selected':'' }} @endisset>Out of stock</option>
                    <option value="onbackorder" @isset($woocommerce_setting->stock_status) {{ $woocommerce_setting->stock_status=='onbackorder' ? 'selected':'' }} @endisset>On backorder</option>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label for="inputEmail4"><b>{{trans('file.Product Status')}}</b></label>
                <select class="form-control" name='product_status'>
                    <option value=''>--Select--</option>
                    <option value="publish" @isset($woocommerce_setting->product_status) {{ $woocommerce_setting->product_status=='publish' ? 'selected':'' }} @endisset>Published</option>
                    <option value="pending" @isset($woocommerce_setting->product_status) {{ $woocommerce_setting->product_status=='pending' ? 'selected':'' }} @endisset>Pending Review</option>
                    <option value="draft" @isset($woocommerce_setting->product_status) {{ $woocommerce_setting->product_status=='draft' ? 'selected':'' }} @endisset>Draft</option>
                </select>
            </div>
        </div>
        <button type="submit" class="mt-3 btn btn-primary">{{trans('file.Submit')}}</button>
    </form>
</div>
