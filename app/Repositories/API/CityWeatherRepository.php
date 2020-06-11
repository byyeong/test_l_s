<?php

namespace App\Repositories\API;

use App\Models\CityWeather;
use App\Repositories\BaseRepository;

/**
 * Class CityWeatherRepository.
 */
class CityWeatherRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return CityWeather::class;
    }

    public function getByDateCity($date, $city_id)
    {
        return $this->model
            ->where('city_id', $city_id)
            ->where('date', $date)
            ->orderBy('id', 'DESC')
            ->first();
    }
}