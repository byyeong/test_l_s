<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityWeather extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'cities_weather';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['city_id', 'date', 'icon', 'temp_max', 'temp_min'];
    protected $hidden = ['updated_at'];
}
