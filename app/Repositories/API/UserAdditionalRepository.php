<?php

namespace App\Repositories\API;

use App\Models\UserAdditional;
use App\Repositories\BaseRepository;

/**
 * Class UserAdditionalRepository.
 */
class UserAdditionalRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return UserAdditional::class;
    }


    public function updateSet($id, $data)
    {
        // print_r($data);
        // exit(1);
        $set = array();
        if (\array_key_exists('gender', $data)) $set['gender'] = $data[ 'gender'];
        if (\array_key_exists('birth', $data)) $set[ 'birth'] = $data[ 'birth'];
        if (\array_key_exists('email', $data)) $set['available_email'] = $data['email'];
        if (\array_key_exists('phone', $data)) $set['phone'] = $data['phone'];
        if (\array_key_exists('job', $data)) $set['job'] = $data['job'];
        if (\array_key_exists('area', $data)) $set['area'] = $data['area'];
        if (\array_key_exists('age_range', $data)) $set['age_range'] = $data['age_range'];

        return $this->model
                ->updateOrCreate(
                    ['user_id' => $id],
                    $set
                );
    }
}