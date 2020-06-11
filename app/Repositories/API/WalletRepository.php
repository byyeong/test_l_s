<?php

namespace App\Repositories\API;

use Illuminate\Support\Facades\DB;
use App\Models\Wallet;
use App\Repositories\BaseRepository;

/**
 * Class WalletRepository.
 */
class WalletRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return Wallet::class;
    }

    public function saveBudget($travel_id, $amount, $currency_id, $id = '')
    {
        $res = '';
        $set = [
            'travel_id' => $travel_id,
            'amount' => $amount,
            'currency_id' => $currency_id
        ];
        if ($id) {
            $res = DB::table('travel_budget')
            ->where('id', $id)
            ->update($set);
        } else {
            $res = DB::table('travel_budget')
            ->insert($set);
        }
        return $res;
    }

    public function getBudget($id)
    {
        return DB::table('travel_budget')->where('travel_id', $id)->first();
    }
}