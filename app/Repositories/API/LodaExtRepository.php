<?php

namespace App\Repositories\API;

use App\Models\LodaExt;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

/**
 * Class LodaExtRepository.
 */
class LodaExtRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return LodaExt::class;
    }
}
