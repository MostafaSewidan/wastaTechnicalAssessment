<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    protected $fillable =[
        "name", "description", "guard_name", "is_active"
    ];
}
