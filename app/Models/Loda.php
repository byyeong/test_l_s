<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loda extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'loda';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'travel_id', 'card_id', 'date', 'onesignal_id', 'endpoint_id'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function card()
    {
        return $this->belongsTo('App\Models\Cards', 'card_id');
    }

    public function ext()
    {
        return $this->morphOne('App\Models\LodaExt', 'loda');
    }
}
