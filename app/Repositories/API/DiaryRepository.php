<?php

namespace App\Repositories\API;

use App\Models\Diary;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

/**
 * Class DiaryRepository.
 */
class DiaryRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return Diary::class;
    }

    public function getBy($field, $value)
    {
        return $this->model
            ->where($field, $value)
            ->first();
    }

    public function logs($diary_id, $user_id, $req)
    {
        if (array_key_exists('lat', $req) || array_key_exists('lon', $req)) {
            $user_dev = DB::table('users_device')->where('user_id', $user_id)->orderBy('id', 'DESC')->first();
            DB::table('diary_logs')->insert([
                'diary_id' => $diary_id,
                'user_id' => $user_id,
                'method' => array_key_exists('method', $req)? $req['method']: '',
                'from' => $user_dev->device_type,
                'service' => 'save place of photo',
                'created_at' => now()
            ]);
        }
    }
}