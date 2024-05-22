<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable =[
        "code", "name", "is_active"
    ];

    public function expense() {
    	return $this->hasMany('App\Models\Expense');
    }
}
