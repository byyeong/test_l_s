<?php

namespace App\Repositories\API;

use App\Models\Cards;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

/**
 * Class CardsRepository.
 */
class CardsRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Cards::class;
    }

    public function getByParent($parent_type, $parent_id) 
    {
        return $this->model
                ->where('parent', $parent_type)
                ->where('parent_id', $parent_id)
                ->first();
    }

    public function getByTypeIn($types)
    {
        return $this->model
                ->whereIn('type', $types)
                ->get();
    }
}
