<?php

namespace Modules\Woocommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WoocommerceSetting extends Model
{
    use HasFactory;

    protected $fillable =[
        'woocomerce_app_url',
        'woocomerce_consumer_key',
        'woocomerce_consumer_secret',
        'default_tax_class',
        'product_tax_type',
        'manage_stock',
        'stock_status',
        'product_status',
        'customer_group_id',
        'warehouse_id',
        'biller_id',
        'order_status_pending',
        'order_status_processing',
        'order_status_on_hold',
        'order_status_completed',
        'order_status_draft',
        'webhook_secret_order_created',
        'webhook_secret_order_updated',
        'webhook_secret_order_deleted',
        'webhook_secret_order_restored',
    ];
}
