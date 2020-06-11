<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Travel.
 */
class Travel extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'travel';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'title', 'adult', 'kids', 'url', 'image', 'start', 'end'];

    protected $hidden = ['user_id', 'created_at', 'updated_at'];

    public function attractions()
    {
        return $this->hasMany('App\Models\TravelAttraction');
    }

    public function pakcingCategories()
    {
        $this->belongsToMany('App\Models\PackingCategory');
    }

    public function todos()
    {
        return $this->hasMany('App\Models\TravelTodo');
    }
}
