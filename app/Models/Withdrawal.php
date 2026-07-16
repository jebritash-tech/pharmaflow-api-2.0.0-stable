<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{

    protected $fillable=[

        'shift_id',

        'user_id',

        'amount',

        'reason'

    ];

    public function shift()
    {

        return $this->belongsTo(Shift::class);

    }

    public function user()
    {

        return $this->belongsTo(User::class);

    }

}
