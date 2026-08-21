<?php

namespace App\Services;

use App\Models\Medicine;
use App\Models\PriceEngineRule;

class PricingService
{
    /*
    |--------------------------------------------------------------------------
    | Resolve Rule For Medicine
    |--------------------------------------------------------------------------
    */

    public function resolveRule(
        Medicine $medicine
    ): ?PriceEngineRule {

        /*
        | Medicine-specific rule
        */

        if ($medicine->pricing_rule_id) {

            $rule = $medicine->pricingRule;

            if (
                $rule &&
                $rule->is_active
            ) {

                return $rule;

            }

        }

        /*
        | Default rule
        */

        return PriceEngineRule::query()

            ->where('is_active', true)

            ->where('is_default', true)

            ->orderBy('sort_order')

            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Sell Price
    |--------------------------------------------------------------------------
    */

    public function calculateSellPrice(
        float $buyPrice,
        Medicine $medicine
    ): array {

        $rule = $this->resolveRule(

            $medicine

        );

        if (!$rule) {

            throw new \RuntimeException(

                'لا توجد قاعدة تسعير مفعلة لهذا الدواء.'

            );
        }

        $rawSellPrice =

            $this->applyRule(

                $buyPrice,

                $rule

            );

        $sellPrice =

            $this->applyRounding(

                $rawSellPrice,

                $rule

            );

        $profitAmount =

            $sellPrice -

            $buyPrice;

        $profitPercent =

            $buyPrice > 0

                ? ($profitAmount / $buyPrice) * 100

                : 0;

        return [

            'rule_id' => $rule->id,

            'buy_price' => round(

                $buyPrice,

                2

            ),

            'raw_sell_price' => round(

                $rawSellPrice,

                2

            ),

            'sell_price' => round(

                $sellPrice,

                2

            ),

            'profit_amount' => round(

                $profitAmount,

                2

            ),

            'profit_percent' => round(

                $profitPercent,

                2

            ),

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Pricing Rule
    |--------------------------------------------------------------------------
    */

    protected function applyRule(
        float $buyPrice,
        PriceEngineRule $rule
    ): float {

        $value = (float) $rule->value;

        return match ($rule->type) {

            /*
            | 40% profit on buy price
            | 100 -> 140
            */

            'percentage' =>

                $buyPrice +

                (

                    $buyPrice *

                    ($value / 100)

                ),

            /*
            | Add fixed amount
            | 100 -> 120 when value = 20
            */

            'fixed' =>

                $buyPrice + $value,

            /*
            | Multiply
            | 100 * 1.4 = 140
            */

            'multiply' =>

                $buyPrice * $value,

            default => throw new \RuntimeException(

                "نوع قاعدة التسعير غير معروف: {$rule->type}"

            ),

        };
    }

    /*
    |--------------------------------------------------------------------------
    | Rounding
    |--------------------------------------------------------------------------
    */

    protected function applyRounding(
        float $price,
        PriceEngineRule $rule
    ): float {

        $settings =

            $rule->roundingSettings();

        $mode =

            $settings['mode'];

        $unit =

            (float) $settings['unit'];

        if (

            $mode === 'none' ||

            $unit <= 0

        ) {

            return $price;
        }

        switch ($mode) {

            case 'nearest':

                return

                    round(

                        $price / $unit

                    ) * $unit;

            case 'up':

                return

                    ceil(

                        $price / $unit

                    ) * $unit;

            case 'down':

                return

                    floor(

                        $price / $unit

                    ) * $unit;

            default:

                throw new \RuntimeException(

                    "سياسة التقريب غير معروفة: {$mode}"

                );

        }
    }
}