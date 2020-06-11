<?php

namespace App\Repositories\API;

use App\Models\Travel;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;

/**
 * Class TravelRepository.
 */
class TravelRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return Travel::class;
    }

    public function getByUser($user, $abbr = null)
    {
        $res = $this->model
                ->where('user_id', $user);
        if ($abbr == 'expected') {
            $res->where('start', '>=', date('Y-m-d'));
        } else if ($abbr == 'gone') {
            $res->where('start', '<', date('Y-m-d'));
        }
        $res->orderBy('start', 'ASC');
        
        return $res->get();
    }

    public function getBy($field, $value)
    {
        return $this->model
            ->where($field, $value)
            ->first();
    }

    public function create($data) 
    {
      $travel = parent::create([
          'user_id' => $data['user_id'],
          'title' => $data['title'],
          'adult' => $data['adult'],
          'kids' => $data['kids'],
          'image' => $data['image'],
      ]);

      return $travel;
    }

    public function getByStart($user, $status)
    {
        $travel = $this->model
                ->where('user_id', $user);
        if ($status === 'expected') {
            $travel->where('end', '>=', date('Y-m-d'));
        } else {
            $travel->where('end', '<', date('Y-m-d'));
        }
        return $travel->orderBy('start')
            ->distinct()
            ->pluck('id');
    }
}