<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Repositories\API\TravelRepository;
use App\Repositories\API\TodoRepository;
use App\Repositories\API\TravelTodoRepository;
use App\Repositories\API\NotesRepository;
use App\Repositories\API\PackingsRepository;
use App\Repositories\API\TravelPackingsRepository;
use App\Repositories\API\TravelPackingsCategoriesRepository;

class ToolController extends BaseController
{
    /**
     * @var TravelRepository
     * @var TodoRepository
     * @var TravelTodoRepository
     * @var PackingsRepository
     * @var TravelPackingsRepository
     * @var TravelPackingsCategoriesRepository
     * @var NotesRepository
     */
    protected $travelRepository;
    protected $todoRepository;
    protected $travelTodoRepository;
    protected $packingRepository;
    protected $travelPackingRepository;
    protected $packingCategoryRepository;
    protected $packingCategoriesRepository;
    protected $notesRepository;


    /**
     * @param TravelRepository $travelRepository
     * @param TodoRepository $todoRepository
     * @param TravelTodoRepository $travelTodoRepository
     * @param PackingsRepository $packingRepository
     * @param TravelPackingsRepository $travelPackingRepository
     * @param TravelPackingsCategoriesRepository $packingCategoriesRepository
     * @param NotesRepository $notesRepository
     */
    public function __construct(TravelRepository $travelRepository,
        TodoRepository $todoRepository,
        TravelTodoRepository $travelTodoRepository,
        PackingsRepository $packingRepository,
        TravelPackingsRepository $travelPackingRepository,
        TravelPackingsCategoriesRepository $packingCategoriesRepository,
        NotesRepository $notesRepository
    ) 
    {
        $this->travelRepository = $travelRepository;
        $this->todoRepository = $todoRepository;
        $this->travelTodoRepository = $travelTodoRepository;
        $this->packingRepository = $packingRepository;
        $this->travelPackingRepository = $travelPackingRepository;
        $this->packingCategoriesRepository = $packingCategoriesRepository;
        $this->notesRepository = $notesRepository;
    }

    private function toolSetter($title, $text, $type, $file, $updated_at, $checked, $total, $tool_id)
    {
        return [
            'title' => $title,
            'text' => $text,
            'type' => $type,
            'file' => $file,
            'updated_at' => $updated_at,
            'checked' => $checked,
            'total' => $total,
            'tool_id' => $tool_id
        ];
    }

    /**
     * Display a listing of the resource.
     * 툴 리스트 
     * todo 현황, packing 현황, 노트 리스트
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $travel_id)
    {
        $tool_list = array();
        // todo
        $todo_cnt = $this->travelTodoRepository
                ->where('travel_id', $travel_id)
                ->count();
        $todo_checked_cnt = $this->travelTodoRepository
                ->getListChecked($travel_id, 1)
                ->count();
        $todo = $this->toolSetter(config('services.tool_name.todo'), null, config('services.tool_type.todo'), null, null, 
                $todo_checked_cnt, $todo_cnt, null);
        array_push($tool_list, $todo);

        // packing
        $packing_checked = $this->packingCategoriesRepository
                ->where('travel_id', $travel_id)
                ->where('checked', 1)
                ->whereNotNull('packings_id')
                ->where('show', config('services.tool_show.show'))
                ->count();
        $packing_all = $this->packingCategoriesRepository
                ->where('travel_id', $travel_id)
                ->whereNotNull('packings_id')
                ->where('show', config('services.tool_show.show'))
                ->count();

        $packing = $this->toolSetter(config('services.tool_name.packings'), null, config('services.tool_type.packings'), null, null, 
                $packing_checked, $packing_all, null);
        array_push($tool_list, $packing);

        // note
        $notes = $this->notesRepository->getListByColumn($travel_id, 'travel_id');
        if (count($notes)) {
            foreach ($notes as $value) {
                $file = $value->files->count() > 0? $value->files[0]: null;
                $note = $this->toolSetter(
                    $value->title,
                    mb_substr($value->contents, 0,50, 'utf-8'),
                    config('services.tool_type.note'),
                    $file,
                    $value->updated_at->format('Y-m-d H:i:s'),
                    null,
                    null,
                    $value->id
                );
                array_push($tool_list, $note);
            }
        }

        return $this->sendResponse($tool_list, '');
    }
}
