<?php

namespace App\Repositories\API;

use App\Models\ToolFile;
use App\Repositories\BaseRepository;

/**
 * Class ToolFileRepository.
 */
class ToolFileRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return ToolFile::class;
    }
}
