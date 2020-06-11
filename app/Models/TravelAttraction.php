<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TravelAttraction.
 */
class TravelAttraction extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'travel_attraction';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['travel_id', 'city_id', 'start_at', 'end_at'];

    public function travel()
    {
        return $this->belongsTo('App\Models\Travel');
    }

    public function city()
    {
        return $this->belongsTo('App\Models\City');
    }
}
