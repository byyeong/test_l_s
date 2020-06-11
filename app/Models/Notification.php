<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['travel_tool_type', 'travel_tool_id', 'times', 'active'];

    public function notiable()
    {
        return $this->morphTo();
    }
}
