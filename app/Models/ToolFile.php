<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolFile extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tool_file';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['tool_type', 'tool_id', 'file', 'file_name', 'ord'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function files()
    {
        return $this->morphTo();
    }
}
