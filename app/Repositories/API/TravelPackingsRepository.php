<?php

namespace App\Repositories\API;

use App\Models\TravelPackings;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;

/**
 * Class TravelPackingRepository.
 */
class TravelPackingsRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return TravelPackings::class;
    }

    public function getBy($field, $value)
    {
        return $this->model
            ->where($field, $value)
            ->first();
    }

    public function getListChecked($travel_id, $checked) 
    {
        $res = $this->model
            ->where('checked', $checked)
            ->where('travel_id', $travel_id)
            ->get();
        
        return $res;
    }

    public function getListBy($travel_id, $value, $where)
    {
        return DB::table('travel_packings')
                ->join('packings', 'packings.id', '=', 'travel_packings.packings_id')
                ->join('packings_category', 'packings.packings_category_id', '=', 'packings_category.id')
                ->where($where, $value)
                ->where('travel_packings.travel_id', $travel_id)
                ->get();
    }

    public function getCheckByCategory($travel_id, $category_id, $checked = null)
    {
        $res = DB::table('packings')
            ->join( 'travel_packings', 'packings.id', '=', 'travel_packings.packings_id')
            ->join( 'packings_category', 'packings_category.id', '=', 'packings.packings_category_id')
            ->where( 'packings_category_id', $category_id);
        if ( $checked != null) {
            $res->where('checked', $checked);
        }
        $res->where('travel_id', $travel_id)
            ->get();

        return $res;
    }

    public function getWhereIds($arr, $where, $val, $key)
    {
        $res = array();
        foreach ($arr as $k => $item) {
            $i = 0;
            if ($item[$where] == $val) {
                $i = $item[$key];
            }
            array_push($res, $i);
        }
        return $res;
    }
}