<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablePosSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_setting', function (Blueprint $table) {
            $table->string('paypal_live_api_username')->nullable()->default(null)->after('stripe_secret_key');
            $table->string('paypal_live_api_password')->nullable()->default(null)->after('paypal_live_api_username');
            $table->string('paypal_live_api_secret')->nullable()->default(null)->after('paypal_live_api_password');
            $table->text('payment_options')->nullable()->default(null)->after('paypal_live_api_secret');
            $table->string('invoice_option',10)->nullable()->default(null)->after('payment_options');
            $table->string('stripe_secret_key')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pos_setting', function (Blueprint $table) {
            $table->dropColumn('paypal_live_api_username');
            $table->dropColumn('paypal_live_api_password');
            $table->dropColumn('paypal_live_api_secret');
            $table->dropColumn('payment_options');
            $table->dropColumn('invoice_option',10);
            $table->dropColumn('stripe_secret_key');
        });
    }
}
