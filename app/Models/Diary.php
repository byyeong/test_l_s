<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diary extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'diary';
    protected $casts = ['travel_id' => 'integer', 'lat' => 'double', 'lon' => 'double', 'temp_max' => 'integer', 'temp_min' => 'integer'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['travel_id', 'date', 'title', 'text', 'lat', 'lon', 'address', 'weather', 'temp_max', 'temp_min', 'gmt'];

    public function files()
    {
        return $this->morphMany('App\Models\ToolFile', 'tool');
    }
}
