<?php

namespace Modules\Woocommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WoocommerceSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_type',
        'operation',
        'records',
        'synced_by'
    ];
}

?>
