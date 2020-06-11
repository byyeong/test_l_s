<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Country.
 */
class Country extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'countries';

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
    protected $fillable = ['name', 'name_en', 'flag'];

    public function cities()
    {
        return $this->hasMany('App\Models\City');
    }
}
