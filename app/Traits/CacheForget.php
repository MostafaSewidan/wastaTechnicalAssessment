<?php
namespace App\Traits;

trait CacheForget {

	public function cacheForget($forget) 
    {
        cache()->forget($forget);
    }
}