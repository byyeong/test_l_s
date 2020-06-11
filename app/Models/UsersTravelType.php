<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UsersTravelType.
 */
class UsersTravelType extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_travel_type';

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
    protected $fillable = ['user_id', 'travel_type_id'];

    public function users()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function detail()
    {
        return $this->belongsTo('App\Models\TravelType', 'travel_type_id');
    }
}
