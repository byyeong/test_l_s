<?php

namespace App\Repositories\API;

use App\Models\UsersTravelType;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class UsersTravelTypeRepository.
 */
class UsersTravelTypeRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return UsersTravelType::class;
    }

    public function updateByUser($user_id, $type_id)
    {
        $set = ['travel_type_id' => $type_id];
        $res = $this->model
                ->where('user_id', $user_id)
                ->update($set);
        
        return $res;
    }

    public function createByUser($user_id, $type_id)
    {
        $set = [
            'travel_type_id' => $type_id,
            'user_id' => $user_id
        ];
        $res = $this->model
            ->create($set);

        return $res;
    }

    public function getStyle($id)
    {
        return DB::table('travel_type')->where('id', $id)->first();
    }
}