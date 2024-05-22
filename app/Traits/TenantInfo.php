<?php
namespace App\Traits;
use App\Models\GeneralSetting;
use App\Tenant;
use DB;
use Cache;
use App\Package;
use App\TenantPayment;

trait TenantInfo {

    public function getTenantId()
    {
        $url = \URL::to('/');
        if(strpos($url, "https://") !== false)
            $url = str_replace("https://", "", $url);
        elseif(strpos($url, "http://") !== false)
            $url = str_replace("http://", "", $url);
        $urlInfo = explode(".", $url);
        return $urlInfo[0];
    }

    public function changePermission($packageData)
    {
        //updating the sql file which tenant will import
        $sqlFile = fopen("public/tenant_necessary.sql", "r");
        $baseSqlData = fread($sqlFile,filesize("public/tenant_necessary.sql"));
        fclose($sqlFile);
        $basicPermission = "(4, 1),(5, 1),(6, 1),(7, 1),(8, 1),(9, 1),(10, 1),(11, 1),(12, 1),(13, 1),(14, 1),(15, 1),(28, 1),(29, 1),(30, 1),(31, 1),(32, 1),(33, 1),(34, 1),(35, 1),(41, 1),(42, 1),(43, 1),(44, 1),(59, 1),(60, 1),(61, 1),(80, 1),(81, 1),(82, 1),(83, 1),(84, 1),(85, 1),(86, 1),(87, 1),(88, 1),(91, 1),(92, 1),(93, 1),(94, 1),(95, 1),(96, 1),(98, 1),(100, 1),(101, 1),(102, 1),(103, 1),(104, 1),(105, 1),(106, 1),(107, 1),(108, 1),(109, 1),(110, 1),(111, 1),(113, 1),(114, 1),(115, 1),(116, 1),(117, 1),(118, 1),(119, 1),(120, 1),(121, 1),(124, 1)";
        //return $basicPermission.','.$packageData->role_permission_values;
        $newSqlData = str_replace($basicPermission, $basicPermission.','.$packageData->role_permission_values, $baseSqlData);
        $sqlFile = fopen("public/tenant_necessary.sql", "w");
        fwrite($sqlFile, $newSqlData);
        fclose($sqlFile);
    }

    public function createTenant($request)
    {
        if(cache()->has('general_setting')){
            $general_setting = cache()->get('general_setting');
        }
        else{
            $general_setting = DB::table('general_settings')->latest()->first();
        }
        $package = Package::select('is_free_trial')->find($request->package_id);
        if($package->is_free_trial)
            $numberOfDaysToExpired = $general_setting->free_trial_limit;
        elseif($request->subscription_type == 'monthly')
            $numberOfDaysToExpired = 30;
        elseif($request->subscription_type == 'yearly')
            $numberOfDaysToExpired = 365;
        if(isset($request->payment_method))
            $paid_by = $request->payment_method;
        else
            $paid_by = '';
        /*if(!$package->is_free_trial && $general_setting->active_payment_gateway == 'stripe') {
            Stripe::setApiKey($general_setting->stripe_secret_key);
            $token = $request->stripeToken;
            $amount = $request->price;
            // Create a Customer:
            $customer = \Stripe\Customer::create([
                'source' => $token
            ]);
            // Charge the Customer instead of the card:
            $charge = \Stripe\Charge::create([
                'amount' => $amount * 100,
                'currency' => $general_setting->currency,
                'customer' => $customer->id
            ]);
            $paid_by = 'Stripe';
        }*/
        //creating tenant
        $tenant = Tenant::create(['id' => $request->tenant]);
        $tenant->domains()->create(['domain' => $request->tenant.'.'.env('CENTRAL_DOMAIN')]);
        if($paid_by) {
            TenantPayment::create(['tenant_id' => $tenant->id, 'amount' => $request->price, 'paid_by' => $paid_by]);
        }
        //updating general setting info in the sql file which tenant will import
        $sqlFile = fopen("public/tenant_necessary_base.sql", "r");
        $baseSqlData = fread($sqlFile,filesize("public/tenant_necessary_base.sql"));
        fclose($sqlFile);
        $newSqlDataForSetting = str_replace("(1, 'SalePro', '20220905125905.png', 0, '1', 0, 'monthly', 'own', 'd/m/Y', 'Lioncoders', 'standard', 1, 'default.css', '2018-07-06 06:13:11', '2022-09-05 06:59:05', 'prefix', '1970-01-01');", "(1, '".$general_setting->site_title."', '".$general_setting->site_logo."', 0, '1', ".$request->package_id.", "."'".$request->subscription_type."', 'own', 'd/m/Y', '".$general_setting->developed_by."', 'standard', 1, 'default.css', '2018-07-06 06:13:11', '2022-09-05 06:59:05', 'prefix', '".date("Y-m-d", strtotime("+".$numberOfDaysToExpired." days"))."');", $baseSqlData);
        $sqlFile = fopen("public/tenant_necessary.sql", "w");
        fwrite($sqlFile, $newSqlDataForSetting);
        fclose($sqlFile);
        //updating user information
        $encryptedPass = '$2y$10$DWAHTfjcvwCpOCXaJg11MOhsqns03uvlwiSUOQwkHL2YYrtrXPcL6';
        $newEncryptedPass = bcrypt($request->password);
        $newSqlDataForUser = str_replace("(1, 'admin', 'admin@gmail.com', '".$encryptedPass."', '6mN44MyRiQZfCi0QvFFIYAU9LXIUz9CdNIlrRS5Lg8wBoJmxVu8auzTP42ZW', '12112', 'lioncoders', 1, NULL, NULL, 1, 0, '2018-06-02 03:24:15', '2018-09-05 00:14:15')", "(1, '".$request->name."', '".$request->email."', '".$newEncryptedPass."', '6mN44MyRiQZfCi0QvFFIYAU9LXIUz9CdNIlrRS5Lg8wBoJmxVu8auzTP42ZW', ".$request->phone_number.",  '".$request->company_name."', 1, NULL, NULL, 1, 0, '2018-06-02 03:24:15', '2018-09-05 00:14:15')", $newSqlDataForSetting);
        $sqlFile = fopen("public/tenant_necessary.sql", "w");
        fwrite($sqlFile, $newSqlDataForUser);
        fclose($sqlFile);
        //updating permission info in the sql file which tenant will import
        $packageData = DB::table('packages')->find($request->package_id);
        $this->changePermission($packageData);

        //updating tenant others information on landlord DB
        $tenant->update(['package_id' => $request->package_id, 'subscription_type' => $request->subscription_type, 'company_name' => $request->company_name, 'phone_number' => $request->phone_number, 'email' => $request->email, 'expiry_date' => date("Y-m-d", strtotime("+".$numberOfDaysToExpired." days"))]);
    }
}
