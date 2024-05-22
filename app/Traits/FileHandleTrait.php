<?php
namespace App\Traits;

use Illuminate\Support\Facades\File;

trait FileHandleTrait{

    protected function fileDelete($filePath, $fileName)
    {
        // if ($fileName && !config('database.connections.saleprosaas_landlord') && File::exists(public_path().$filePath.$fileName))
        //    File::delete(public_path().$filePath.$fileName);
        // else if($fileName && File::exists($filePath.$fileName))
        //    File::delete($filePath.$fileName);


        if($fileName && !config('database.connections.saleprosaas_landlord') && file_exists('public/'.$filePath.$fileName))
            unlink('public/'.$filePath.$fileName);
        elseif($fileName && file_exists($filePath.$fileName))
            unlink($filePath.$fileName);

    }

}
