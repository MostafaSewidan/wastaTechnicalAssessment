<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrmSetting extends Model
{
    protected $fillable =[
        "checkin", "checkout"
    ];
}
