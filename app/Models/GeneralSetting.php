<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $fillable =[
        "site_title", "site_logo", "is_rtl", "currency", "currency_position", "staff_access", "without_stock", "date_format", "theme", "developed_by", "phone", "email", "free_trial_limit", "package_id", "invoice_format","decimal", "state", "expiry_date", "subscription_type", "meta_title", "meta_description", "active_payment_gateway", "stripe_public_key", "stripe_secret_key", "paypal_client_id", "paypal_client_secret", "razorpay_number", "razorpay_key", "razorpay_secret", "is_zatca", "company_name", "vat_registration_number"
    ];
}
