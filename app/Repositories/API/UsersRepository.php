<?php

namespace App\Repositories\API;

use App\Models\Users;
use App\Repositories\BaseRepository;

/**
 * Class UsersRepository.
 */
class UsersRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Users::class;
    }
}