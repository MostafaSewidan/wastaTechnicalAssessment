<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWoocommerceSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woocommerce_settings', function (Blueprint $table) {
            // API Settings
            $table->increments('id');
            $table->string('woocomerce_app_url')->nullable();
            $table->string('woocomerce_consumer_key')->nullable();
            $table->string('woocomerce_consumer_secret')->nullable();

            // Product Sync Settings
            $table->string('default_tax_class')->nullable();
            $table->string('product_tax_type')->nullable();
            $table->string('manage_stock')->nullable();
            $table->string('stock_status')->nullable();

            // Order Sync Settings
            $table->tinyInteger('customer_group_id')->nullable();
            $table->tinyInteger('warehouse_id')->nullable();
            $table->tinyInteger('biller_id')->nullable();
            $table->tinyInteger('order_status_pending')->nullable();
            $table->tinyInteger('order_status_processing')->nullable();
            $table->tinyInteger('order_status_on_hold')->nullable();
            $table->tinyInteger('order_status_completed')->nullable();
            $table->tinyInteger('order_status_draft')->nullable();

            // Webhook Settings
            $table->string('webhook_secret_order_created')->nullable();
            $table->string('webhook_secret_order_updated')->nullable();
            $table->string('webhook_secret_order_deleted')->nullable();
            $table->string('webhook_secret_order_restored')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woocommerce_settings');
    }
}
