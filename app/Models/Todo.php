<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'todo';

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
    protected $fillable = ['title', 'type', 'can_delete'];
    protected $hidden = ['ord'];

    public function travels()
    {
        return $this->hasOne('App\Models\TodoTravel');
    }
}
