<div class="tab-pane fade show" id="list-webhook-settings" role="tabpanel" aria-labelledby="list-webhook-settings-list">

    <form action="{{route('woocommerce.store')}}" method="POST">
        @csrf
        <input type="hidden" name="webhook_settings" value="1">

        <h3>Order Created</h3>
        <div class="form-row">
            <div class="form-group col-md-6">
                <p class="text-dark">Webhook Secret :</p>
                <input type="text" name="webhook_secret_order_created" value="{{$woocommerceSetting->webhook_secret_order_created ?? null}}"  class="form-control" placeholder="Webhook Secret">
            </div>
            <div class="form-group col-md-6">
                <p class="text-dark">Webhook Delivery URL:</p>
                <p>https://pos.ultimatefosters.com/webhook/order-created/1</p>
            </div>
        </div>

        <h3>Order Updated</h3>
        <div class="form-row">
            <div class="form-group col-md-6">
                <p class="text-dark">Webhook Secret :</p>
                <input type="text" name="webhook_secret_order_updated" value="{{$woocommerceSetting->webhook_secret_order_updated ?? null}}" class="form-control" id="inputEmail4" placeholder="Webhook Secret">
            </div>
            <div class="form-group col-md-6">
                <p class="text-dark">Webhook Delivery URL:</p>
                <p>https://pos.ultimatefosters.com/webhook/order-created/1</p>
            </div>
        </div>

        <h3>Order Deleted</h3>
        <div class="form-row">
            <div class="form-group col-md-6">
                <p class="text-dark">Webhook Secret :</p>
                <input type="text" class="form-control"  name="webhook_secret_order_deleted" value="{{$woocommerceSetting->webhook_secret_order_deleted ?? null}}" placeholder="Webhook Secret">
            </div>
            <div class="form-group col-md-6">
                <p class="text-dark">Webhook Delivery URL:</p>
                <p>https://pos.ultimatefosters.com/webhook/order-created/1</p>
            </div>
        </div>

        <h3>Order Restored</h3>
        <div class="form-row">
            <div class="form-group col-md-6">
                <p class="text-dark">Webhook Secret :</p>
                <input type="text" class="form-control"   name="webhook_secret_order_restored" value="{{$woocommerceSetting->webhook_secret_order_restored ?? null}}" placeholder="Webhook Secret">
            </div>
            <div class="form-group col-md-6">
                <p class="text-dark">Webhook Delivery URL:</p>
                <p>https://pos.ultimatefosters.com/webhook/order-created/1</p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

</div>
