<?php

namespace App\Repositories\API;

use App\Models\Categories;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;

/**
 * Class TravelRepository.
 */
class CategoriesRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Categories::class;
    }

    public function getByInParent($where, $value, $parent)
    {
        return $this->model
                ->whereIn($where, $value)
                ->where('parent', $parent)
                ->get();
    }

    public function getByTitle($title, $type, $parent)
    {
        $res = $this->model
            ->where('parent', $parent)
            ->where('type', $type)
            ->where('title', $title)
            ->first();
        if ( !$res) {
            $set = [
                'title' => $title,
                'type' => $type,
                'parent' => $parent
            ];
            $res = parent::create($set);
        }
        return $res;
    }

    public function getByParent($parent, $parent_id)
    {
        return $this->model
                ->where('parent', $parent)
                ->where('parent_id', $parent_id)
                ->first();
    }
}