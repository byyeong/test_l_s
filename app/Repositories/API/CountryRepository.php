<?php

namespace App\Repositories\API;

use App\Models\Country;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;

/**
 * Class CountryRepository.
 */
class CountryRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return Country::class;
    }

    public function getBy($field, $value)
    {
        return $this->model
            ->where($field, $value)
            ->first();
    }

    public function getCurrency($country_id)
    {
        $res = DB::table('countries')
          ->join('countries_currency', 'countries_currency.country_id', '=', 'countries.id')
          ->join('currencies', 'currencies.id', '=', 'countries_currency.currency_id')
          ->where('countries.id', $country_id)
          ->get(['currencies.*']);

        return $res;
    }
}