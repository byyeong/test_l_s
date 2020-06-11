<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelPackings extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'travel_packings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['packings_id', 'travel_id', 'checked', 'qty'];

    public function packing()
    {
        return $this-> belongsTo('App\Models\Packings');
    }
}
