<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    
    protected $table = "customer_addresses";
    protected $guarded = ['id'];
    protected $fillable =[
        "address_text", "city_text", "customer_id", "state_text",
         "is_active"
    ];

}
