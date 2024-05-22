<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;

class DsoAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dsoalert:find';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find all products who could not fulfill daily sale objective';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = date("Y-m-d", strtotime("-1 day"));
        config()->set('database.connections.mysql.strict', false);
        DB::reconnect();
        $lims_dso_alert_products = DB::table('products')
                                ->leftJoin('product_sales', function($join) {
                                    $join->on('products.id', '=', 'product_sales.product_id')
                                         ->whereNotNull('products.daily_sale_objective');
                                })
                                ->where('products.daily_sale_objective', '>', function($query) {
                                    $query->select(DB::raw("sum(product_sales.qty)"));
                                })
                                ->whereDate('product_sales.created_at', $date)
                                ->select('products.name', 'products.code')
                                ->groupBy('products.code')
                                ->get();
        config()->set('database.connections.mysql.strict', true);
        DB::reconnect();
        $number_of_products = count($lims_dso_alert_products);
        echo $lims_dso_alert_products;
        //inserting data
        if($number_of_products) {
            DB::table('dso_alerts')
            ->insert([
                'product_info' => $lims_dso_alert_products,
                'number_of_products' => $number_of_products,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
