<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelTodo extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'travel_todo';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['travel_id', 'todo_id', 'checked', 'ord'];

    public function todo()
    {
        return $this->belongsTo('App\Models\Todo');
    }

    public function notification()
    {
        return $this->morphOne('App\Models\Notification', 'travel_tool');
    }
}
