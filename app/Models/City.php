<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class City.
 */
class City extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'cities';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'name_en', 'image', 'place_id', 'country_id', 'lng', 'lat'];

    public function country()
    {
        return $this->belongsTo('App\Models\Country');
    }

    public function attractions()
    {
        return $this->hasMany('App\Models\TravelAttraction');
    }

    public function currency()
    {
        return $this->belongsTo('App\Models\Currency');
    }
}
