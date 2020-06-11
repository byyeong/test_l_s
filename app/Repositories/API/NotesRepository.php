<?php

namespace App\Repositories\API;

use App\Models\Notes;
use App\Repositories\BaseRepository;

/**
 * Class TravelRepository.
 */
class NotesRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return Notes::class;
    }

    public function getBy($field, $value)
    {
        return $this->model
            ->where($field, $value)
            ->first();
    }
}