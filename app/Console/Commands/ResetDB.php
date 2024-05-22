<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

class ResetDB extends Command
{
    use \App\Traits\CacheForget;

    protected $signature = 'reset:db';

    protected $description = 'Reset DB in the demo';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //clearing all the cached queries
        $this->cacheForget('biller_list');
        $this->cacheForget('brand_list');
        $this->cacheForget('category_list');
        $this->cacheForget('coupon_list');
        $this->cacheForget('customer_list');
        $this->cacheForget('customer_group_list');
        $this->cacheForget('product_list');
        $this->cacheForget('product_list_with_variant');
        $this->cacheForget('warehouse_list');
        $this->cacheForget('table_list');
        $this->cacheForget('tax_list');
        $this->cacheForget('currency');
        $this->cacheForget('general_setting');
        $this->cacheForget('pos_setting');
        $this->cacheForget('user_role');
        $this->cacheForget('permissions');
        $this->cacheForget('role_has_permissions');
        $this->cacheForget('role_has_permissions_list');
        //clearing all data from the DB
        $tables = DB::select('SHOW TABLES');
        $str = 'Tables_in_' . env('DB_DATABASE');
        foreach ($tables as $table) {
            DB::table($table->$str)->truncate();    
        }
        //importing data from DB
        DB::unprepared(file_get_contents('public/salepro_data.sql'));
    }
}
