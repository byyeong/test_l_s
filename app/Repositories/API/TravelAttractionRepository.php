<?php

namespace App\Repositories\API;

use App\Models\TravelAttraction;
use App\Repositories\BaseRepository;

/**
 * Class TravelAttractionRepository.
 */
class TravelAttractionRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return TravelAttraction::class;
    }

    public function getBy($field, $value)
    {
        return $this->model
            ->where($field, $value)
            ->first();
    }

    public function create($data)
    {
      $att = parent::create([
        'travel_id' => $data['travel_id'],
        'city_id' => $data['city_id'],
        'start_at' => $data['start_at'],
        'end_at' => $data['end_at'],
      ]);

      return $att;
    }

    public function getByTravel($travel_id)
    {
      return $this->model
        ->where('travel_id', $travel_id)
        ->get();
    }
}