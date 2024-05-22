@extends('backend.layout.main')
@section('content')
    <div class="container">

        @include('woocommerce::includes.nav')

        <div class="row">
            <div class="col-3">
                <div class="list-group" id="list-tab" role="tablist">
                    <a class="list-group-item list-group-item-action active" id="list-instruction-list" data-toggle="list"
                        href="#list-instruction" role="tab" aria-controls="instruction">{{trans('file.Instructions')}}</a>
                    <a class="list-group-item list-group-item-action" id="list-api-settings-list" data-toggle="list"
                        href="#list-api-settings" role="tab" aria-controls="api-settings">{{trans('file.API Settings')}}</a>
                    <a class="list-group-item list-group-item-action" id="list-product-sync-settings-list" data-toggle="list"
                        href="#list-product-sync-settings" role="tab" aria-controls="product-sync-settings">{{trans('file.Product Sync Settings')}}</a>
                    <a class="list-group-item list-group-item-action" id="list-order-sync-settings-list" data-toggle="list"
                        href="#list-order-sync-settings" role="tab" aria-controls="order-sync-settings">{{trans('file.Order Sync Settings')}}</a>
                    {{-- <a class="list-group-item list-group-item-action" id="list-webhook-settings-list" data-toggle="list"
                        href="#list-webhook-settings" role="tab" aria-controls="webhook-settings">{{trans('file.Webhook Settings')}}</a> --}}
                </div>
            </div>
            <div class="col-9">
                <div class="card">
                    <div class="card-body">
                        <div class="tab-content" id="nav-tabContent">

                            @include('woocommerce::includes.api_settings.instruction')

                            @include('woocommerce::includes.api_settings.api-settings')

                            @include('woocommerce::includes.api_settings.product_sync_settings')

                            @include('woocommerce::includes.api_settings.order-sync-settings')

                            {{-- @include('woocommerce::includes.api_settings.webhook-settings') --}}
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection
