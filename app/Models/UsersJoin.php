<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersJoin extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_join';

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
    protected $fillable = ['user_id', 'sns_type', 'sns_account', 'key', 'secret'];
    protected $hidden = [];
}
