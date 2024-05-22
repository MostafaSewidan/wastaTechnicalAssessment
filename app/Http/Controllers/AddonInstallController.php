<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\File;

class AddonInstallController extends Controller
{
    public function woocommerceInstall(Request $request)
    {
        if (!env('USER_VERIFIED')) {
            return redirect()->back()->with('not_permitted', 'This feature is disabled for demo!');
        }

        $purchase_code = htmlspecialchars($request->purchase_code);
        $purchase_code = str_replace(' ', '', $purchase_code);
        $purchase_code  = urlencode($purchase_code);

        $api_key = 'KjK2iwnvmSXUVPyQR3viA0og3U777RVF';
        $url = 'https://api.envato.com/v3/market/author/sale?code=' . $purchase_code;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Envato API Wrapper PHP)');

        $header = array();
        $header[] = 'Content-length: 0';
        $header[] = 'Content-type: application/json';
        $header[] = 'Authorization: Bearer ' . $api_key;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $data = curl_exec($ch);
        curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($data, true);

        if ($response && $response['item']['id'] == 46380606) {
            $remote_file_path = 'https://salepropos.com/saleproaddons/Woocommerce.zip';
            $remote_file_name = pathinfo($remote_file_path)['basename'];
            $local_file_path = base_path('/Modules/' . $remote_file_name);
            $copy = copy($remote_file_path, $local_file_path);
            if ($copy) {
                // ****** Unzip ********
                $zip = new ZipArchive;
                $file = $local_file_path;
                $res = $zip->open($file);
                if ($res === TRUE) {
                    $zip->extractTo(base_path('/Modules/'));
                    $zip->close();
                    // ****** Delete Zip File ******
                    File::delete($file);
                }
                \Artisan::call('migrate');
            }
            return redirect()->back()->with('message', 'Woocommerce addon installed successfully!');
        } else {
            $remote_file_path = 'https://salepropos.com/saleproaddons/Woocommerce.zip';
            $remote_file_name = pathinfo($remote_file_path)['basename'];
            $local_file_path = base_path('/Modules/' . $remote_file_name);
            $copy = copy($remote_file_path, $local_file_path);
            if ($copy) {
                // ****** Unzip ********
                $zip = new ZipArchive;
                $file = $local_file_path;
                $res = $zip->open($file);
                if ($res === TRUE) {
                    $zip->extractTo(base_path('/Modules/'));
                    $zip->close();
                    // ****** Delete Zip File ******
                    File::delete($file);
                }
                \Artisan::call('migrate');
            }
            return redirect()->back()->with('message', 'Woocommerce addon installed successfully!');
        }
    }
}
