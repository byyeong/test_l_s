<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notes extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'notes';
    protected $casts = ['travel_id' => 'integer'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['travel_id', 'title', 'contents'];

    public function files()
    {
        return $this->morphMany('App\Models\ToolFile', 'tool');
    }
}
