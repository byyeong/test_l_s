<?php

namespace App\Repositories\API;

use App\Models\Currency;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class CurrencyRepository.
 */
class CurrencyRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Currency::class;
    }

    public function getDefault() 
    {
        // 원화, 달러
        return $this->model
            ->whereIn('id', [135, 5])
            ->orderBy('id', 'DESC')
            ->get();

    }

    public function saveTravelCurrency($travel_id, $currency_id, $rate)
    {
        $res = null;

        $ex = DB::table('travel_currency')
            ->where('travel_id', $travel_id)
            ->where('currency_id', $currency_id)
            ->first();
        
        if ($ex) {
            $res = DB::table('travel_currency')
                ->where('id', $ex->id)
                ->update([
                'amount' => $rate,
                'created_at' => now()
            ]);
        } else {
            $res = DB::table('travel_currency')->insert([
                'travel_id' => $travel_id,
                'currency_id' => $currency_id,
                'amount' => $rate,
                'created_at' => now()
            ]);
        }
        return $res;
    }

    public function getExchange($travel_id, $currency_id) 
    {
        return DB::table('travel_currency')
                ->where('travel_id', $travel_id)
                ->where('currency_id', $currency_id)
                ->first();
    }

    public function lately_currency($currency_id)
    {
        return DB::table('travel_currency')
            ->where('currency_id', $currency_id)
            ->orderBy('id', 'DESC')
            ->first();
    }
}