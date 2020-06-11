<?php

namespace App\Repositories\API;

use App\Models\TravelPackingsCategories;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

/**
 * Class TravelPackingsCategoriesRepository.
 */
class TravelPackingsCategoriesRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return TravelPackingsCategories::class;
    }
    public function getListByTravel($travel_id)
    {
        return $this->model
            ->where('travel_id', $travel_id)
            ->orderBy('id')
            ->get();
    }
    public function getByTypeParent($travel_id, $type, $parent)
    {
        return DB::table('travel_packings_categories')
            ->join('categories', 'categories.id', '=', 'travel_packings_categories.categories_id')
            ->where('travel_id', '=', $travel_id)
            ->where('type', $type)
            ->where('parent', $parent)
            // ->groupBy('categories_id')
            ->get();
    }
    public function getCheckByCategory($travel_id, $categories_id, $checked)
    {
        $res = DB::table('travel_packings_categories')
            ->join('categories', 'categories.id', '=', 'travel_packings_categories.categories_id')
            ->where('travel_id', '=', $travel_id)
            ->where('categories_id', $categories_id);
        if ($checked) {
            $res->where('checked', $checked);
        }
        $res->get();
        return $res;
    }
    public function getCategoryByTravel($travel_id)
    {
        return $this->model
            ->where('travel_id', $travel_id)
            ->get();
    }
    public function getPackingByTravel($travel_id, $show = '')
    {
        $res =  $this->model
            ->where('travel_id', $travel_id);
        if ($show != '') {
            $res->where('show', $show);
        }
        return $res->orderBy('categories_id')->get();
    }
    public function getPackingByTravelInCategory($travel_id, $categories_id, $show = '')
    {
        $res =  $this->model
            ->where('travel_id', $travel_id)
            ->whereIn('categories_id', $categories_id);
        if ($show != '') {
            $res->where('show', $show);
        }
        return $res->orderBy('categories_id')->get();
    }
    public function getOnlyPackingByTravel($travel_id, $show = '', $checked = '')
    {
        $res =  $this->model
            ->whereNotNull('packings_id')
            ->where('travel_id', $travel_id);
        if ($show != '') {
            $res->where('show', $show);
        }
        if ($checked != '') {
            $res->where('checked', $checked);
        }
        return $res->orderBy('categories_id')->get();
    }
    public function getPackingInByCategory($travel_id, $category_ids)
    {
        return $this->model
            ->whereIn('travel_id', [0, $travel_id])
            ->whereIn('categories_id', $category_ids)
            ->whereNotNull('packings_id')
            ->get();
    }
    public function getMyByCategory($travel_id, $category_ids)
    {
        return $this->model
            ->where('travel_id', $travel_id)
            ->whereIn('categories_id', $category_ids)
            ->get();
    }
    public function getMyByCategoryABBR($travel_id, $category_id)
    {
        return $this->model
            ->where('travel_id', $travel_id)
            ->where('categories_id', $category_id)
            ->whereNotNull('packings_id')
            ->orderBy('id')
            ->get();
    }
    public function deleteMyByCategory($travel_id, $category_ids)
    {
        return $this->model
            ->where('travel_id', $travel_id)
            ->whereIn('categories_id', $category_ids)
            ->delete();
    }
    public function deleteByTravel($travel_id)
    {
        return $this->model
            ->where('travel_id', $travel_id)
            ->delete();
    }
    public function store($data)
    {
        $set = [
            'categories_id' => $data['categories_id'],
            'travel_id' => $data['travel_id'],
            'checked' => $data['checked']
        ];
        $res = parent::create($set);
        return $res;
    }
    public function updateByCategory($new_category_id, $category_id, $travel_id)
    {
        return $this->model
            ->where('travel_id', $travel_id)
            ->where('categories_id', $category_id)
            ->update([
                'categories_id' => $new_category_id
            ]);
    }
    public function resetCheck($travel_id)
    {
        return $this->model
            ->where('travel_id', $travel_id)
            ->where('show', config('services.tool_show.show'))
            ->update([
                'checked' => config('services.tool_checked.unchecked')
            ]);
    }
    public function getByCategoryPackingTravel($category_id, $packing_id, $travel_id)
    {
        return $this->model
            ->where('packings_id', $packing_id)
            ->where('categories_id', $category_id)
            ->where('travel_id', $travel_id)
            ->first();
    }
    public function hiddenByCategory($travel_id, $category_id, $show)
    {
        return $this->model
            ->where('travel_id', $travel_id)
            ->where('categories_id', $category_id)
            ->update([
                'show' => $show
            ]);
    }
}

