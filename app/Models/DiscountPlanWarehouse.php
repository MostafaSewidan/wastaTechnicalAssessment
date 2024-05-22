<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountPlanWarehouse extends Model  // تم تغيير الاسم هنا
{
    use HasFactory;

    protected $fillable = ['discount_plan_id', 'warehouse_id'];  // تم تغيير الاسم هنا
}
