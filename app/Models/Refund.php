<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    //
    protected $fillable = ['sale_id', 'amount', 'reason'];
}
