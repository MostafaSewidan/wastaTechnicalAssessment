<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountPlan extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    public function warehouses()  // تم تغيير الاسم هنا
    {
        return $this->belongsToMany('App\Models\Warehouse', 'discount_plan_warehouses');  // تم تغيير الاسم هنا
    }
}
