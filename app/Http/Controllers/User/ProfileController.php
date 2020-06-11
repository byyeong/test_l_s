<?php

namespace App\Http\Controllers\User;

use Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Notifications\EmailVerifyRequest;
use App\Http\Controllers\BaseController;
use App\Repositories\API\UsersTravelTypeRepository;
use App\Repositories\API\UserAdditionalRepository;
use App\Repositories\API\LodaRepository;
use App\Repositories\API\TravelRepository;
use App\Repositories\API\TodoRepository;
use App\Repositories\API\CardsRepository;
use App\User;

class ProfileController extends BaseController
{
    /**
     * @var UsersTravelTypeRepository
     * @var userAdditionalRepository
     * @var lodaRepository
     * @var travelRepository
     * @var todoRepository
     * @var cardsRepository
     */
    protected $usersTravelTypeRepository;
    protected $userAdditionalRepository;
    protected $lodaRepository;
    protected $travelRepository;
    protected $todoRepository;
    protected $cardsRepository;

    /**
     * @param UsersTravelTypeRepository $usersTravelTypeRepository
     * @param UserAdditionalRepository $usersTravelTypeRepository
     * @param UsersRepository $lodaRepository
     * @param TravelRepository $travelRepository
     * @param TodoRepository $todoRepository
     * @param CardsRepository $cardsRepository
     */
    public function __construct(UsersTravelTypeRepository $usersTravelTypeRepository,
            UserAdditionalRepository $userAdditionalRepository, LodaRepository $lodaRepository,
            TravelRepository $travelRepository, TodoRepository $todoRepository,
            CardsRepository $cardsRepository) 
    {
        $this->usersTravelTypeRepository = $usersTravelTypeRepository;
        $this->userAdditionalRepository = $userAdditionalRepository;
        $this->lodaRepository = $lodaRepository;
        $this->travelRepository = $travelRepository;
        $this->todoRepository = $todoRepository;
        $this->cardsRepository = $cardsRepository;
    }



    
    public function index()
    {
        $user = request()->user();
        if ($user->image == '/') $user->image = '';
        $sns_join = DB::table('users_join')
                ->select('sns_type', 'sns_account', 'key', 'secret')
                ->where('user_id', $user->id)->get();
        $additional_info = $this->userAdditionalRepository->where('user_id', $user->id)->get();
        $travel_type = $this->usersTravelTypeRepository->getByColumn($user->id, 'user_id');
        $data['user_id'] = $user->id;
        $data['name'] = $user->name;
        $data['profile_url'] = $user->image? $user->image: null;
        $data['travel_type'] = $travel_type? $travel_type->detail: null;
        $data['email_join'] = [
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at
        ];
        $data['sns_join'] = $sns_join->count() > 0? $sns_join: null;
        $data['additional'] = $additional_info->count()? $additional_info[0]: null;
        $data['acceptance'] = [
            'general_push' => $user->general_push,
            'acceptance_sms' => $user->acceptance_sms,
            'acceptance_push' => $user->acceptance_push,
            'acceptance_email' => $user->acceptance_email,
        ];

        return $this->sendResponse($data, 'User successfully.');
    }



    public function travelType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|exists:travel_type,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        $user = $request->user();
        $travel_type = $this->usersTravelTypeRepository->getByColumn($user->id, 'user_id');

        $usersTravelType = '';
        if ($travel_type) {
            $this->usersTravelTypeRepository->updateByUser($user->id, $request->only('type')['type']);
            $usersTravelType = $travel_type->fresh();
        } else {
            $usersTravelType = $this->usersTravelTypeRepository->createByUser($user->id, $request->only('type')['type']);
        }

        if (! $usersTravelType) {
            return $this->sendError(config('message.exception.SRV_ERR'), 'server error');
        } else {
            $res = $this->usersTravelTypeRepository->getStyle( $usersTravelType->travel_type_id);
        }

        
        return $this->sendResponse( $res, '');
    }



    public function emailVerify()
    {
        $user = request()->user();
        if ($user->email_verified_at) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'user aleady verified.');
        }
        $user->notify(new EmailVerifyRequest($user->email_token));
        
        $success['email'] = $user->email;
        return $this->sendResponse($success, 'Send Email Success.');
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function emailVerified(Request $request)
    {
        $res = 1;
        if ($request->secure()) {
            if ($request->route('id') != $request->user()->getKey()) {
                $res = 0;
            }

            if ($request->user()->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }
        } elseif (strtotime('now') < $request->expires) {
            // TODO
            DB::table('users')->where('id', $request->route('id'))->update([
                'email_verified_at' => now()
            ]);
        } elseif (strtotime('now') > $request->expires) {
            $res = 0;
        }

        return view('emailVerified')
                ->withRes($res);
    }

    

    public function additional(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gender' => 'integer|max:3',
            'birth' => '',
            'job' => 'integer|max:14',
            'area' => 'integer|max:16',
            'phone' => '',
            'email' => 'email',
            'age_range' => 'integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $req = $request->all();
        $user = $request->user();
        
        $res = $this->userAdditionalRepository->updateSet($user->id, $req);
        return $this->sendResponse($res);
    }

    public function accept(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'acceptance_push' => 'boolean',
            'general_push' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        $req = $request->all();
        $user = $request->user();
        $set = array();
        $acceptValue = null;
        if ( \array_key_exists('acceptance_push', $req)) {
            if ($req['acceptance_push']) $acceptValue = now();
            else {
                try {
                    // 예약된 goods 로다 카드 지우기
                    $goods_card_ids = $this->cardsRepository->getByTypeIn([config('services.loda_card.goods'), config('services.loda_card.any')])->pluck('id');
                    $loda = $this->lodaRepository->getByUserFromDateInType($user->id, now(), $goods_card_ids, 1000);
                    foreach ($loda as $l) {
                        if ($l->onesignal_id) {
                            cancelPush($l->onesignal_id);
                            $this->lodaRepository->updateById($l->id, [
                                'onesignal_id' => ''
                            ]);
                        }
                        $this->lodaRepository->deleteById($l->id);
                    }
                } catch (\Throwable $th) {
                    return $this->sendError(config('message.exception.SRV_ERR'), 'fail to remove loda card');
                }
                
            }
            $set = [
                'acceptance_sms' => $acceptValue,
                'acceptance_email' => $acceptValue,
                'acceptance_push' => $acceptValue,
            ];
            
            if ($user->player_id) {
                $val = $req['acceptance_push'] ? 'true' : 'false';
                setOnesignalTags($user->player_id, config('services.onesignal_tags.mkp'), $val);
            }
        }
        
        if (\array_key_exists('general_push', $req)) {
            $set['general_push'] = $req['general_push'];
        }
        $res = DB::table('users')->where('id', $user->id)->update($set);
        return $this->sendResponse($res);
    }

    public function acceptList(Request $request)
    {
        $res = $request->user()->only( 'general_push', 'acceptance_sms', 'acceptance_push', 'acceptance_email');
        return $this->sendResponse($res);
    }

    public function signoff()
    {
        $user = request()->user();

        DB::beginTransaction();
        try {
            // email 삭제
            $user->update([
                'email' => null
            ]);
            // loda 삭제
            $loda = $this->lodaRepository->where('user_id', $user->id)->get();
            foreach ($loda as $value) {
                if ($value['onesignal_id'] && $value['date'] >= date('Y-m-d')) {
                    // push 예정 데이터 삭제
                    cancelPush($value['onesignal_id']);
                }
            }
            $this->lodaRepository->where('user_id', $user->id)->delete();
            // sns 삭제
            DB::table('users_join')->where('user_id', $user->id)->delete();
            // user 삭제
            User::where('id', $user->id)->delete();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollback();
            return $this->sendError(config('message.exception.SRV_ERR'), 'fail remove user');
        }
        DB::commit();
        exit(1);
    }

    /**
     * notification list by user travel 
     *
     * @return \Illuminate\Http\Response
     */
    public function notification(Request $request)
    {
        $user = $request->user();
        $travels = $this->travelRepository->where('user_id', $user->id)->where('end', now(), '>=')->with('todos.notification')->get();

        foreach ($travels as $i => $t) {
            if ( isset($t->todos)) {
                foreach ($t->todos as $k => $tt) {
                    if ($tt->notification == null) unset($t->todos[$k]);
                    else $t->todos[$k]['todo'] = $this->todoRepository->getById( $t->todos[$k]->todo_id);
                }
                $travels[$i]->todos->filter()->values();
                if ($t->todos->count() == 0) {
                    unset($travels[$i]);
                } else {
                    $todo = $travels[$i]->todos->filter()->values();
                    unset($travels[$i]['todos']);
                    $travels[$i]['todos'] = $todo;
                }
                
                
                
            }
        }
        return $this->sendResponse($travels->filter()->values(), '');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'max:191',
            'player_id' => 'max:255',
            'password' => 'min:8|max:32',
            'ad' => 'array',
            'ad.*.id' => 'max:255',
            'ad.*.at' => 'max:255',
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $req = $request->all();
        $user = $request->user();
        
        $input = [];
        if (array_key_exists('name', $req)) $input['name'] = $req['name'];
        if (array_key_exists('player_id', $req)) {
            $input['player_id'] = $req['player_id'];
            $val = $user->acceptance_push? 'true': 'false';
            setOnesignalTags($req['player_id'], config('services.onesignal_tags.mkp'), $val);
        }

        if (array_key_exists('password', $req)) $input['password'] = bcrypt($req['password']);
        $res = $user->update($input);

        if (array_key_exists('ad', $req)) {
            foreach ($req['ad'] as $key => $value) {
                $ex = DB::table('users_ad')->where('user_id', $user->id)->where('ad_id', $value['id'])->where('ad_at', $value['at'])->first();
                if (!$ex && $value['id']) {
                    $res = DB::table('users_ad')->insert([
                        'user_id' => $user->id,
                        'ad_id' => $value['id'],
                        'ad_at' => $value['at']
                    ]);
                }
            }
        }
        return $this->sendResponse($res);
    }


    public function name(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $user = $request->user();
        $user->update($request->only('name'));

        return $this->sendResponse();
    }

    public function password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8|max:32',
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = $request->user();
        $user->update($input);

        return $this->sendResponse();
    }

    public function picture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'image|max:' . config('services.file_limit.size'),
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $input = $request->all();
        $file = $input['image'];
        $name = time() . $file->getClientOriginalName();
        $filePath = 'user/profile/' . $name;
        Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');

        // 기존 이미지 삭제
        $user = $request->user();
        if ($user->image) {
            Storage::disk('s3')->delete(substr($user->image, 1));
        }

        // 새 이미지
        if ($filePath) {
            $input['image'] = '/' . $filePath;
            $user->update($input);
        } else {
            return $this->sendError(config('message.exception.IVD_ARG'), 'failed image upload');
        }

        $success['image'] = '/' . $filePath;
        return $this->sendResponse($success);
    }

    public function makeToken(Request $request)
    {
        $st = \substr(env('APP_KEY'), 5, 7) . ($request->user()->id * 6009) . date('his');
        $token = Crypt::encryptString($st);

        $res['token'] = $token;
        return $this->sendResponse($res);
    }

    public function tourCnt(Request $request, $type)
    {
        $cnt = 0;
        $user = $request->user();
        if ($type == 'order') {
            $cnt = DB::table('tour_payment')
                ->where('user_id', $user->id)
                ->count();
        } elseif ($type == 'cart') {
            $cnt = DB::table('tour_cart')
                ->where('date', '>=', date('Y-m-d'))
                ->where('user_id', $user->id)
                ->groupBy('goods_id', 'date')
                ->get()->count();
        } elseif ($type == 'wishlist') {
            $cnt = DB::table('tour_wishlist')
                ->where('user_id', $user->id)
                ->count();
        }
        $res['type'] = $type;
        $res['count'] = $cnt;
        return $this->sendResponse($res);
    }
}

