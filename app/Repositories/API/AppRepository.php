<?php

namespace App\Repositories\API;

use App\Models\App;
use App\Repositories\BaseRepository;

/**
 * Class AppRepository.
 */
class AppRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return App::class;
    }
}