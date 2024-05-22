@extends('backend.layout.main')
@section('content')
    <div class="container-fluid">

        @include('woocommerce::includes.nav')


        {{-- Alert Message --}}
        @include('woocommerce::includes.alert_message')

        <section>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fa fa-tags"></i> {{trans('file.Sync Product Categories')}}</h3>
                        </div>
                        <div class="card-body">
                            @if(!empty($notify['not_synced_cat']) || !empty($notify['updated_cat']))
                            <h3 class="mb-3 text-center">
                                @if(!empty($notify['not_synced_cat']))
                                <span>{{$notify['not_synced_cat']}}</span>
                                <br>
                                @endif
                                @if(!empty($notify['updated_cat']))
                                <span>{{$notify['updated_cat']}}</span>
                                @endif
                            </h3>
                            @endif
                            <div class="row mb-3">
                                <div class="col-6"><button id="sync-categories" type="button" class="btn btn-primary w-100"><i class="fa fa-handshake-o"></i> {{trans('file.Sync')}}</button></div>
                                <div class="col-6"> <button id="reset-categories" type="button" class="btn btn-warning w-100"><i class="fa fa-undo"></i> {{trans('file.Reset Synced Category')}}</button></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fa fa-cubes"></i> {{trans('file.Sync Products')}}</h3>
                        </div>
                        <div class="card-body">
                            @if(!empty($notify['not_synced_product']) || !empty($notify['not_updated_product']))
                            <h3 class="mb-3 text-center">
                                @if(!empty($notify['not_synced_product']))
                                <span>{{$notify['not_synced_product']}}</span>
                                <br>
                                @endif
                                @if(!empty($notify['not_updated_product']))
                                <span>{{$notify['not_updated_product']}}</span>
                                @endif
                            </h3>
                            @endif
                            <div class="row mb-3">
                                <div class="col-6"><button id="sync-products" type="button" class="btn btn-primary w-100"><i class="fa fa-handshake-o"></i> {{trans('file.Sync')}}</button></div>
                                <div class="col-6"> <button id="reset-products" type="button" class="btn btn-warning w-100"><i class="fa fa-undo"></i> {{trans('file.Reset Synced Product')}}</button></div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="row">


                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fa fa-arrows-h"></i> {{trans('file.Tax Rates Mapping')}}</h3>
                        </div>
                        <div class="card-body">
                            <form id="mapping-tax">
                                @csrf
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{trans('file.POS Tax Rate')}}</th>
                                            <th>{{trans('file.Equivalent WooCommerce Tax Rate')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($tax_rates))
                                        @foreach($tax_rates as $tax_rate)
                                            <tr>
                                                <td>{{$tax_rate->name}}:</td>
                                                <td>{!! Form::select('taxes[' . $tax_rate->id . ']', $woocommerce_tax_rates, $tax_rate->woocommerce_tax_id, ['class' => 'form-control tax-select']) !!}</td>
                                            </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                                <button id="map-tax" type="button" class="btn btn-primary form-control">{{trans('file.Submit')}}</button>
                            </form>
                        </div>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fa fa-cart-plus"></i> {{trans('file.Sync Orders(New Order Only)')}}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-12"><button id="sync-orders" type="button" class="btn btn-primary w-100"><i class="fa fa-handshake-o"></i> {{trans('file.Sync')}}</button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </div>


@endsection


@push('scripts')

<script type="text/javascript">
    (function($) {
        "use strict";
        $(document).ready( function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            let syncingTxt = '<i class="fa fa-refresh fa-spin"></i> ' + "Syncing...";

            function ifReloadPage() {
                    event.preventDefault();
                    event.returnValue = '';
            }

            $('#sync-categories').on('click', function() {
                window.addEventListener('beforeunload', ifReloadPage);
                let btnHtml = $(this).html();
                $(this).html(syncingTxt);
                $(this).attr('disabled', true);
                $.ajax({
                    type: "POST",
                    url: "{{route('woocommerce.syncCategories')}}",
                    dataType: "json",
                    timeout: 0,
                    success: function(result){
                        console.log(result);
                        alert(result.msg);
                        $('#sync-categories').html(btnHtml);
                        $('#sync-categories').removeAttr('disabled');
                        window.removeEventListener('beforeunload', ifReloadPage);
                        location.reload();
                    }
                });
            });

            $('#reset-categories').on('click', function() {
                var r = confirm("Really want to reset?");
                if (r == true) {
                    window.addEventListener('beforeunload', ifReloadPage);
                    $(this).attr('disabled', true);
                    $.ajax({
                        type: "POST",
                        url: "{{route('woocommerce.resetSyncedCategory')}}",
                        dataType: "json",
                        timeout: 0,
                        success: function(result){
                            alert(result.msg);
                            $('#reset-categories').removeAttr('disabled');
                            window.removeEventListener('beforeunload', ifReloadPage);
                            location.reload();
                        }
                    });
                }
            });

            $('#sync-products').on('click', function(){
                @if(!empty($notify['not_synced_cat']))
                alert('First sync categorires');
                @else
                    window.addEventListener('beforeunload', ifReloadPage);
                    let btnHtml = $(this).html();
                    $(this).html(syncingTxt);
                    $(this).attr('disabled', true);
                    $.ajax({
                        type: "POST",
                        url: "{{route('woocommerce.syncProducts')}}",
                        dataType: "json",
                        timeout: 0,
                        success: function(result){
                            alert(result.msg);
                            $('#sync-products').html(btnHtml);
                            $('#sync-products').removeAttr('disabled');
                            window.removeEventListener('beforeunload', ifReloadPage);
                            location.reload();
                        }
                    });
                @endif
            });

            $('#reset-products').on('click', function(){
                var r = confirm("Really want to reset?");
                if (r == true) {
                    window.addEventListener('beforeunload', ifReloadPage);
                    $(this).attr('disabled', true);
                    $.ajax({
                        type: "POST",
                        url: "{{route('woocommerce.resetSyncedProduct')}}",
                        dataType: "json",
                        timeout: 0,
                        success: function(result){
                            alert(result.msg);
                            $('#reset-products').removeAttr('disabled');
                            window.removeEventListener('beforeunload', ifReloadPage);
                            location.reload();
                        }
                    });
                }
            });

            $('select.tax-select').change(function() {
                var select_value1 = $(this).val();
                $('select.tax-select').not(this).each(function() {
                    var select_value2 = $(this).val();
                    if (select_value1 == select_value2) {
                        $(this).val('');
                        $(this).selectpicker('render');
                    }
                });
            });

            $('#map-tax').on('click', function(event){
                var form_data = $('#mapping-tax').serialize();
                window.addEventListener('beforeunload', ifReloadPage);
                let btnHtml = $(this).html();
                $(this).html(syncingTxt);
                $(this).attr('disabled', true);
                $.ajax({
                    type: "POST",
                    url: "{{route('woocommerce.mapTaxRates')}}",
                    dataType: "json",
                    data:form_data,
                    timeout: 0,
                    success: function(result){
                        console.log(result);
                        alert(result.msg);
                        $('#map-tax').html(btnHtml);
                        $('#map-tax').removeAttr('disabled');
                        window.removeEventListener('beforeunload', ifReloadPage);
                        location.reload();
                    }
                });
            });

            $('#sync-orders').on('click', function(){
                window.addEventListener('beforeunload', ifReloadPage);
                let btnHtml = $(this).html();
                $(this).html(syncingTxt);
                $(this).attr('disabled', true);
                $.ajax({
                    type: "POST",
                    url: "{{route('woocommerce.syncOrders')}}",
                    dataType: "json",
                    timeout: 0,
                    success: function(result){
                        console.log(result);
                        alert(result.msg);
                        $('#sync-orders').html(btnHtml);
                        $('#sync-orders').removeAttr('disabled');
                        window.removeEventListener('beforeunload', ifReloadPage);
                        //location.reload();
                    }
                });
            });

        });
    })(jQuery);
</script>
@endpush
