<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Debt;
class DebtController extends Controller
{
    //
    public function index()
    {

        return Debt::latest()->get();

    }
}
