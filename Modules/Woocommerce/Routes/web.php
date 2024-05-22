<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['common', 'auth', 'active']], function() {
    Route::get('/woocommerce', 'WoocommerceController@index')->name('woocommerce.index');
    Route::get('/woocommerce/sync-log', 'WoocommerceController@syncLog')->name('woocommerce.sync-log');
    Route::get('/woocommerce/settings', 'WoocommerceController@settings')->name('woocommerce.settings');
    Route::post('/woocommerce/store', 'WoocommerceController@store')->name('woocommerce.store');
    Route::post('/woocommerce/sync-categories', 'WoocommerceController@syncCategories')->name('woocommerce.syncCategories');
    Route::post('/woocommerce/reset-sync-category', 'WoocommerceController@resetSyncedCategory')->name('woocommerce.resetSyncedCategory');
    Route::post('/woocommerce/sync-product', 'WoocommerceController@syncProducts')->name('woocommerce.syncProducts');
    Route::post('/woocommerce/reset-sync-product', 'WoocommerceController@resetSyncedProduct')->name('woocommerce.resetSyncedProduct');

    Route::post('/woocommerce/map-tax-rates', 'WoocommerceController@mapTaxRates')->name('woocommerce.mapTaxRates');

    Route::post('/woocommerce/sync-orders', 'WoocommerceController@syncOrders')->name('woocommerce.syncOrders');

});


