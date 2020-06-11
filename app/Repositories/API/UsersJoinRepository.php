<?php

namespace App\Repositories\API;

use Illuminate\Support\Facades\DB;
use App\Models\UsersJoin;
use App\Repositories\BaseRepository;

/**
 * Class UsersJoinRepository.
 */
class UsersJoinRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return UsersJoin::class;
    }
}