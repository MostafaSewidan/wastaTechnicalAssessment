<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = ["belongs_to", "name", "type", "default_value", "option_value", "grid_value", "is_table", "is_invoice", "is_required", "is_admin", "is_disable"];
}
