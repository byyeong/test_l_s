<?php

namespace App\Repositories\API;

use App\Models\City;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;

/**
 * Class CityRepository.
 */
class CityRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return City::class;
    }

    public function getBy($field, $value)
    {
        if ($field == 'place_id') $value = getTargetPlaceId($value);
        $check = $this->model
            ->where($field, $value)
            ->first();

        $city = '';
        if ( !$check && $field == 'place_id') {
          // place_id group check (도시 정리 후에 주석 해제)
          // $value = getTargetPlaceId($value);
          // $check = $this->model
          //       ->where($field, $value)
          //       ->first();
          // if ($check) {
          //     return $check;
          // }
          // 
          $language = [ 0 => 'en', 1 => 'ko'];
          foreach ($language as $lang) {

            $cityByGoogle = cityDetailBygoogle($value, $lang);

            if ( $cityByGoogle['status'] != 'OK') {
                throw new GeneralException(config('message.exception.NO_CT'));
            }
            $addres = $cityByGoogle['result']['address_components'];
            $city_name = $cityByGoogle['result']['name'];
            $lat = $cityByGoogle['result'][ 'geometry'][ 'location'][ 'lat'];
            $lng = $cityByGoogle['result']['geometry']['location']['lng'];

            
            $country_code = '';
            foreach ($addres as $val) {
              foreach ($val['types'] as $k => $v) {
                if ($v == 'country') {
                  $country_code = $val['short_name'];
                  $country_name = $val['long_name'];
                }
              }
            } 
  
            if ($country_code && $city_name) {
                // 국가 가져오기
                $ct_info = DB::table('countries')->where('alpha2Code', $country_code)->first();

                if ($lang == 'en') {
                    $params = [
                      'name_en' => $city_name,
                      'place_id' => $value,
                      'country_id' => $ct_info->id,
                      'lat' => $lat,
                      'lng' => $lng,
                      'image' => '/city/rep/'. str_replace(' ', '_', strtolower($city_name))  . '.jpg'
                    ];

                    $city = parent::create($params);
                } else {
                    // 국문일때, 국가 한글명 업데이트
                    if (!$country_name) {
                      DB::table('countries')->where('id', $ct_info->id)->update([
                        'name' => $country_name
                      ]);
                    }
                    if ($city) {
                        $params = [
                          'name' => $city_name
                        ];

                        $city = parent::updateById($city->id, $params);
                    }
                }
                
            } else {
                throw new GeneralException(config('message.exception.NO_CT'));
            }
          }
            
        } else {
          $city = $check;
        }
        return $city;
    }

    public function getLanguage($id) 
    {
        return DB::table('languages')->where('id', $id)->first();
    }

    public function getCurrency($id)
    {
        return DB::table('currencies')->where('id', $id)->first();
    }

    public function getLanguageByCountry($country_id)
    {
        return DB::table('countries_language')
            ->join('languages', 'languages.id', '=', 'countries_language.language_id')
            ->where('countries_language.country_id', $country_id)
            ->orderBy( 'languages.id')
            ->first([ 'languages.*']);
    }

    public function getCurrencyByCountry($country_id)
    {
      return DB::table('countries_currency')
        ->join( 'currencies', 'currencies.id', '=', 'countries_currency.currency_id')
        ->where( 'countries_currency.country_id', $country_id)
        ->orderBy( 'currencies.id')
        ->first([ 'currencies.*']);
    }
}