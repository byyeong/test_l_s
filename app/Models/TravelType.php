<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TravelType.
 */
class TravelType extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'travel_type';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'description'];

    public function users()
    {
        return $this->hasOne('App\Models\User');
    }
}
