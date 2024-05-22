<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable =[
        "amount", "customer_id", "user_id", "note"
    ];
}
