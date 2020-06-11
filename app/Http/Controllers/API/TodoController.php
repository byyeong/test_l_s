<?php

namespace App\Http\Controllers\API;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\BaseController;
use App\Repositories\API\TodoRepository;
use App\Repositories\API\TravelTodoRepository;
use App\Repositories\API\NotificationRepository;
use App\Repositories\API\CardsRepository;
use App\Repositories\API\LodaRepository;
use App\Repositories\API\TravelRepository;


class TodoController extends BaseController
{
    /**
     * @var TodoRepository
     * @var TravelTodoRepository
     * @var NotificationRepository
     * @var CardsRepository
     * @var LodaRepository
     * @var TravelRepository
     */
    protected $todoRepository;
    protected $travelTodoRepository;
    protected $notificationRepository;
    protected $cardsRepository;
    protected $lodaRepository;
    protected $travelRepository;




    /**
     * @param TodoRepository $todoRepository
     * @param TravelTodoRepository $travelTodoRepository
     * @param NotificationRepository $notificationRepository
     * @param CardsRepository $cardsRepository
     * @param LodaRepository $lodaRepository
     * @param TravelRepository $travelRepository
     */
    public function __construct(TodoRepository $todoRepository, TravelTodoRepository $travelTodoRepository, NotificationRepository $notificationRepository,
                CardsRepository $cardsRepository, LodaRepository $lodaRepository, TravelRepository $travelRepository) 
    {
        $this->todoRepository = $todoRepository;
        $this->travelTodoRepository = $travelTodoRepository;
        $this->notificationRepository = $notificationRepository;
        $this->cardsRepository = $cardsRepository;
        $this->lodaRepository = $lodaRepository;
        $this->travelRepository = $travelRepository;
    }




    /**
     * Display the specified resource.
     *
     * @param  int  $travel_id
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $travel_id)
    {
        $todos = $this->travelTodoRepository->getListByColumn($travel_id, 'travel_id');

        $res['base'] = array();
        $res['custom'] = array();
        foreach ($todos as $prv) {
            $prv->notification;
            $prv->todo;
            if ($prv->todo->type === config('services.tool_personalization.base')) {
                $cards = $this->cardsRepository->where('type', 'todo')->where('parent', 'todo')->where('parent_id', $prv->todo->id)->get();
                if ($cards->count()) $prv->todo->target = $cards[0]['target'];
                array_push($res['base'], $prv);
            } else {
                array_push($res['custom'], $prv);
            }
        }

        return $this->sendResponse($res, '');
    }

    /**
     * Store a check resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function check(Request $request, $travel_id, $travel_todo_id, $check)
    {
        $vali = ['check' => $check]; 
        $validator = Validator::make($vali, [
            'check' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $trv_todo = $this->travelTodoRepository->where('travel_id', $travel_id)->where('id', $travel_todo_id)->first();
        if (! $trv_todo) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no todo item to check');
        }
        
        $pushed = '';
        DB::beginTransaction();

        try {
            // travel todo status update
            $this->travelTodoRepository->updateById($travel_todo_id, ['checked' => $check]);

            // todo item
            $todoItem = $this->todoRepository->getById($trv_todo->todo_id);
            if ($todoItem->type === 'base') { // 기본 할일
                // loda card push
                $card = $this->cardsRepository->getByParent(config('services.tool_type.todo'), $trv_todo->todo_id);
                $user = $request->user();
                // loda card and push
                $push = $this->lodaRepository->check($travel_id, $card->id, $travel_todo_id);

                if ($check == config('services.tool_checked.checked')) { // $check === 1
                    if ($push) {
                        if ($push->date > date('Y-m-d H:i:s')) {
                            // push 예정 - loda 삭제(카드, push 삭제)
                            $this->lodaRepository->deleteRow($push);
                        } // push가 이미 나간 경우는 손대지 않아.
                    }
                     
                } else {  // $check === 0

                    // 발송일
                    $travel = $this->travelRepository->getById($travel_id);
                    $timestamp = strtotime($travel->start . ' -' . $card->dday . ' days');
                    $recive_date = date('Y-m-d ' . config('services.push.time'), $timestamp);
                    
                    // 발송일이 오늘 이후면 발송
                    if ($recive_date > date('Y-m-d')) {
                        // loda 체크
                        if (!$push) {
                            $push = $this->lodaRepository->createBySet($user->id, $travel_id, $trv_todo->id, $card->id, $recive_date);

                            if ($push) {
                                // push 생성
                                $onesignal = createPush($card->title, $card->contents, array_merge($card->toArray(), $push->toArray()) , [$user->id], $recive_date);
                                $onesignal_res = json_decode($onesignal, true);
                                if (\array_key_exists('id', $onesignal_res)) {
                                    $pushed = $onesignal_res['id'];
                                    $this->lodaRepository->updateById($push->id, ['onesignal_id' => $onesignal_res['id']]);
                                }
                            }
                        }
                    }                    
                } 
            } else { //사용자 할일
                // 로컬 알림
                $noti = $this->travelTodoRepository->getById($travel_todo_id)->notification;
                if ($check == config('services.tool_checked.checked') && $noti) { 
                    // check가 1이고, 로컬 알림이 있을 경우
                    $this->travelTodoRepository->getById($travel_todo_id)->notification->update([
                        'active' => config('services.tool_checked.unchecked')
                    ]);;
                }
            }
        } catch (\Throwable $th) {
            DB::rollback();
            
            // 푸시 보낸게 있으면 취소
            if ($pushed) cancelPush($pushed); 

            return $this->sendError(config('message.exception.SRV_ERR'), 'fail to check');
        }
        
        DB::commit();
    }

    /**
     * Store a check resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkReset(Request $request, $travel_id)
    {
        $todos = $this->travelTodoRepository
                ->where('travel_id', $travel_id)
                ->where('checked', config('services.tool_checked.checked'))
                ->get();
        foreach ($todos as $todo) {
            $this->check($request, $travel_id, $todo->id, config('services.tool_checked.unchecked'));
        };
        exit(1);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $travel_id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'times' => 'date_format:Y-m-d H:i'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        
        $res = '';
        DB::beginTransaction();
        try {
            // travel todo pre check
            $todo_txt = $this->todoRepository->where('title', $request->only('title')['title'])->get();
            if ($todo_txt->count() > 0) {
                $travelTodo = $this->travelTodoRepository->where('travel_id', $travel_id)->where('todo_id', $todo_txt[0]->id)->get();
                if ($travelTodo->count()) {
                    return $this->sendError(config('message.exception.IVD_ARG'), 'duplicated todo');
                }
            }

            $todo = $this->todoRepository->getTitelAndType($request->only('title')['title'], config('services.tool_personalization.custom'), config('services.tool_delete.custom'));
            $item = [
                "travel_id" => $travel_id,
                "todo_id" => $todo->id,
                "checked" => config('services.tool_checked.unchecked')
            ];
            $todoItem = $this->travelTodoRepository->create($item);

            if ($request->only('times')) {
                $data = [
                    "travel_tool_type" => config('services.tool_model.todo'),
                    "travel_tool_id" => $todoItem->id,
                    "times" => $request->only('times')['times']
                ];

                $this->notificationRepository->create($data);
            }
            $res = $todoItem;
            
        } catch (QueryException $th) {
            DB::rollback();
            return $this->sendError(config('message.exception.TODO_C_FAIL'), 'duplicated todo or create fail');
        }
        DB::commit();
        
        if ($res) {
            $res->todo;
            $res->notification;
            $res->travel_id = (int)$res->travel_id;
            return $this->sendResponse($res, '');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $travel_id, $todo_id)
    {
        $todo = $this->travelTodoRepository->where('id', $todo_id)->where('travel_id', $travel_id)->first();
        $data = $request->all();

        if (! array_key_exists("title",$data) && ! array_key_exists("times",$data)) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no resource');
        }

        // 할일 변경
        if ( array_key_exists("title",$data) && $data['title'] && $todo->todo->title != $data['title']) {
            if ( $todo->todo->type == config('services.tool_personalization.custom')) {
                $todoItem = $this->todoRepository->getTitelAndType($data['title'], config('services.tool_personalization.custom'), config('services.tool_delete.custom'));
                $this->travelTodoRepository->updateById($todo_id, ['todo_id' => $todoItem->id]);
            } else {
                return $this->sendError(config('message.exception.IVD_ARG'), 'not edit base type title');
            }
            
        };

        // 알림 변경
        if ( array_key_exists("times", $data)) {
            if ($data['times'] == null && $todo->notification) {
                $todo->notification->delete();
            }

            if ($data['times']) {

                // date validation check
                $validator = Validator::make($request->all(), [
                    'times' => 'date_format:Y-m-d H:i'
                ]);
                if ($validator->fails()) {
                    return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
                }

                // 기존 값과 다른 경우 삭제
                if ($todo->notification) {
                    if ($todo->notification->times != $data['times']) {
                        $todo->notification->delete();
                    }
                }

                // 새로 알림 생성
                $set = [
                    "travel_tool_type" => config('services.tool_model.todo'),
                    "travel_tool_id" => $todo_id,
                    "times" => $data['times']
                ];

                $this->notificationRepository->create($set);
            } 
        }
        
        $res = $this->travelTodoRepository->getById($todo_id);
        $res->todo;
        $res->notification;
        return $this->sendResponse($res, '');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $travel_id, $todo_id
     * @return \Illuminate\Http\Response
     */
    public function delete($travel_id, $todo_id)
    {
        $todo = $this->travelTodoRepository->where('id', $todo_id)->where('travel_id', $travel_id)->first();

        try {
            $res = DB::transaction(function () use ($todo) {
                if ( $todo->notification) {
                    $todo->notification->delete();
                }
                $todo->delete();
            });

            if ($res == true) {
                return $this->sendResponse('', '', 204);
            }
        } catch (QueryException $th) {
            return $this->sendError(config('message.exception.SRV_ERR'), 'delete fail');
        }  
    } 



    
    public function pushTest()
    {
        $user_id = Input::get('user_id');
        $travel_id = Input::get('travel_id');
        $todo_id = Input::get('todo_id');
        $datetime = Input::get('datetime');
        $todo = $this->todoRepository->getById($todo_id);
        $card = $this->cardsRepository->where('type', 'todo')->where('parent_id', $todo_id)->first();

        if ($card->count() < 1) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no card for todo');
        }

        $loda_set = [
            'user_id' => $user_id,
            'travel_id' => $travel_id,
            'card_id' => $card->id,
            'times' => substr($datetime, 0, 10)
        ];
        $loda = $this->lodaRepository->create($loda_set);

        $heading = array(
            "en" => strip_tags($card->title),
            "kr" => strip_tags($card->title)
        );
        $content = array(
            "en" => strip_tags($card->contents),
            "kr" => strip_tags($card->contents)
        );

        $data = [
            'user_id' => $user_id,
            'travel_id' => $travel_id,
            'data' => $card
        ];

        $fields = array(
            'app_id' => config('services.onesignal.app_id'),
            'include_external_user_ids' => [$user_id],
            'send_after' => $datetime . ':00 GMT+0900',
            'delayed_option' => 'timezone',
            'data' => $data,
            'contents' => $content,
            'headings' => $heading,
            'content_available' => true,
            'mutable_content' => true
        );

        $fields = json_encode($fields);
        Log::info('onesignal push : ' . $fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . config('services.onesignal.rest_api_key')
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($response, true);
        if (array_key_exists('id', $res)) {
            $loda->update(['onesignal_id' => $res['id']]);
        };

        // success : {"id":"e9996741-cc00-4b2f-9a4d-61b3fe25d0db","recipients":1,"external_id":null}
        // fail : {"errors":["Schedule Notifications may not be scheduled in the past."]}
        return $this->sendResponse($response, '');
    }
}
