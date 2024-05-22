<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSetting extends Model
{
    protected $table = 'pos_setting';
    protected $fillable =[
        "customer_id", "warehouse_id", "biller_id", "product_number", "stripe_public_key", "stripe_secret_key","paypal_live_api_username","paypal_live_api_password","paypal_live_api_secret","payment_options","invoice_option", "keybord_active", "is_table"
    ];
}
