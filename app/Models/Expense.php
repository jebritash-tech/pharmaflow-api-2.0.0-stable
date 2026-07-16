<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{

    protected $fillable=[

        'shift_id',

        'user_id',

        'amount',

        'title',

        'notes'

    ];

    public function shift()
    {

        return $this->belongsTo(
            Shift::class
        );

    }

    public function user()
    {

        return $this->belongsTo(
            User::class
        );

    }

}
