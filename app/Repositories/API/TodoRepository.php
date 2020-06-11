<?php

namespace App\Repositories\API;

use App\Models\Todo;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;

/**
 * Class TravelRepository.
 */
class TodoRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return Todo::class;
    }

    public function getBy($field, $value)
    {
        return $this->model
            ->where($field, $value)
            ->first();
    }

    public function create($data) 
    {
      $todo = parent::create([
          "title" => $data["title"],
          "type" => $data["type"],
          "can_delete" => $data["can_delete"]
      ]);

      return $todo;
    }

    public function getTitelAndType($title, $type, $can_delete)
    {
        $todo = $this->model
            ->where('title', $title)
            ->where('type', $type)
            ->first();

        if ( !$todo) {
            $data = [
              "title" => $title,
              "type" => $type,
              "can_delete" => $can_delete
            ];
            $todo = $this->create($data);
        }

        return $todo;
    }
}