<?php

namespace App\Repositories\API;

use App\Models\Packings;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;

/**
 * Class TravelRepository.
 */
class PackingsRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Packings::class;
    }

    public function getBy($field, $value)
    {
        return $this->model
            ->where($field, $value)
            ->first();
    }

    public function getListByType($item, $column, array $columns = ['*'])
    {
        return DB::table('packings_category')
            ->join('packings', 'packings.id', '=', 'packings_category.packings_id')
            ->where($column, $item)
            ->get($columns);
    }

    public function create($data) 
    {
        $travel = parent::create([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'adult' => $data['adult'],
            'kids' => $data['kids'],
            'image' => $data['image'],
        ]);

        return $travel;
    }

    public function updateCategoryId($old, $new)
    {
        return $this->model->where('packings_category_id', $old)
                ->update([
                    'packings_category_id' => $new
                ]);
    }

    public function getByTitle($title)
    {
        $res = $this->model
            ->where('title', $title)
            ->first();
        if (!$res) {
            $set = [
                'title' => $title
            ];
            $res = parent::create($set);
        }
        return $res;
    }
}