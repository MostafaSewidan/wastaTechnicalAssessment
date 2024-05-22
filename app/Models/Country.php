<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    /**
     * This Model has been created for testing purpose(Seeder, Factory, PHPUnit Testing and others).
     * It will be deleted later.
     *
     */


    protected $table = 'countries';
    protected $fillable = ['code', 'name'];

}
