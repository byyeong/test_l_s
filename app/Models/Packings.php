<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Packings extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'packings';

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
    protected $fillable = ['title', 'category_id'];

    public function travels()
    {
        return $this->hasMany('App\Models\PackingCategory');
    }

    public function travel()
    {
        return $this->hasOne('App\Models\PackingTravel');
    }
}
