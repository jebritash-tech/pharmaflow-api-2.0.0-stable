<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\MedicineBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PurchaseService;
use App\Http\Requests\PurchaseRequest;


class PurchaseController extends Controller
{

    public function store(
        PurchaseRequest $request
    )
    {
        DB::beginTransaction();

        try {

            $purchase = app(
                PurchaseService::class
            )->store($request->validated());

            DB::commit();

            return response()->json(
                $purchase->load(
                    'items',
                    'supplier'
                )
            );

        }

        catch (\Throwable $e){

            DB::rollBack();

            throw $e;

        }

    }

}