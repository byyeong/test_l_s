<?php

namespace App\Repositories\API;

use App\Models\TravelTodo;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;

/**
 * Class TravelTodoRepository.
 */
class TravelTodoRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return TravelTodo::class;
    }

    public function getBy($field, $value)
    {
        return $this->model
            ->where($field, $value)
            ->first();
    }

    public function getListChecked($travel_id, $checked = null) 
    {
        $res = $this->model::query();
        if ( $checked !== null) {
            $res->where( 'checked', $checked);
        }
        $res->where('travel_id', $travel_id)
                ->get();
        
        return $res;
    }

    public function getListCheckedByTodoType($travel_id, $type)
    {
        return $this->model
                ->join('todo', 'todo.id', '=', 'travel_todo.todo_id')
                ->where('travel_id', $travel_id)
                ->where('type', $type)
                ->get();
    }

    public function getWithTodo($id)
    {
        return $this->model
                ->where('id', $id)
                ->with('todo')
                ->first();
    }

    public function getByTravelandTodo($travel_id, $todo_id)
    {
        return $this->model
                ->where('travel_id', $travel_id)
                ->where('todo_id', $todo_id)
                ->first();
    }
}