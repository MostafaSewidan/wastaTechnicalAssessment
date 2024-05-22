<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWithCheque extends Model
{
    protected $table = 'payment_with_cheque';

    protected $fillable =[

        "payment_id", "cheque_no"
    ];
}
