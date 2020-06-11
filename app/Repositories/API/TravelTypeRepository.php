<?php

namespace App\Repositories\API;

use App\Models\TravelType;
use App\Repositories\BaseRepository;

/**
 * Class TravelTypeRepository.
 */
class TravelTypeRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return TravelType::class;
    }
}