<?php

namespace App\Http\Controllers\API;

use Validator; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use League\OAuth2\Server\Exception\OAuthServerException;
use App\Http\Controllers\BaseController;
use App\Exceptions\GeneralException;
use App\Repositories\API\CityRepository;
use App\Repositories\API\CountryRepository;
use App\Repositories\API\TravelRepository;
use App\Repositories\API\TravelAttractionRepository;
use App\Repositories\API\CardsRepository;
use App\Repositories\API\LodaRepository;
use App\Repositories\API\TravelTodoRepository;


class TravelController extends BaseController
{
  /**
   * @var CityRepository
   * @var CountryRepository
   * @var TravelRepository
   * @var TravelAttractionRepository
   * @var CardsRepository
   * @var LodaRepository
   * @var TravelTodoRepository
   */
  protected $cityRepository;
  protected $countryRepository;
  protected $travelRepository;
  protected $travelAttractionRepository;
  protected $cardsRepository;
  protected $lodaRepository;
  protected $travelTodoRepository;


  /**
   * @param CityRepository $cityRepository
   * @param CountryRepository $countryRepository
   * @param TravelRepository $travelRepository
   * @param TravelAttractionRepository $travelAttractionRepository
   * @param CardsRepository $cardsRepository
   * @param LodaRepository $lodaRepository
   * @param TravelTodoRepository $travelTodoRepository
   */
  public function __construct(CityRepository $cityRepository, CountryRepository $countryRepository, 
          TravelRepository $travelRepository, TravelAttractionRepository $travelAttractionRepository,
          CardsRepository $cardsRepository, LodaRepository $lodaRepository,
    TravelTodoRepository $travelTodoRepository)
  {
    $this->cityRepository = $cityRepository;
    $this->countryRepository = $countryRepository;
    $this->travelRepository = $travelRepository;
    $this->travelAttractionRepository = $travelAttractionRepository;
    $this->cardsRepository = $cardsRepository;
    $this->lodaRepository = $lodaRepository;
    $this->travelTodoRepository = $travelTodoRepository;
  }

  /**
   * 여행 첫 이미지
   *
   * @param  [string] place_id
   * @param  [string] name
   * @param  [string] name_en
   * @return [string] image url
   */
  public function firstImage()
  {
    $validator = Validator::make(Input::all(), [
      'place_id' => 'required',
    ]);

    if ($validator->fails()) {
      return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
    }

    $city = $this->cityRepository->getBy('place_id', Input::get('place_id'));

    $res_image = '';
    
    $exists = Storage::disk('s3')->exists($city->image);
    
    if ($exists) {
      $res_image = $city->image;
    } else {
      $country = $this->countryRepository->getById($city->country_id);
      $exists_c = Storage::disk('s3')->exists($country->image);

      if ($exists_c) {
          $res_image = $country->image;
      } else {
          $key = array_rand(config('services.travel_default_img'));
          $res_image = config('services.travel_default_img')[$key];
      }
      
    }



    $data = [
      'image' => $res_image,
    ];

    return $this->sendResponse($data, '');
  }

  /**
   * 나라 정보
   *
   * @param  [string] value
   * @param  [string] type
   * @param  [string] field : special filed return
   * @return [model] County 
   */
  public function countryInfo()
  {
    $validator = Validator::make(Input::all(), [
      'value' => 'required',
      'type' => 'required',
      'field' => '',
    ]);

    if ($validator->fails()) {
      return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
    }

    $countryInfo = '';
    $key = Input::get('type');
    $val = Input::get('value');
    $field = Input::get('field');
    try {
      if ($key == 'place_id') {
        $city = $this->cityRepository->getBy($key, $val);
        $countryInfo = $city->country;
      }
    } catch (\Throwable $th) {
      return $this->sendError(config('message.exception.NO_CNT'), '');
    }

    $res = [];
    if ($field) {
      $fields = explode(',', $field);
      foreach ($fields as $key => $value) {
        $res[$value] = $value === 'flag' ? $countryInfo[$value] : $countryInfo[$value];
      }
    } else {
      $res = $countryInfo;
    }

    return $this->sendResponse($res, '', 201);
  }

  /**
   * 여행 생성하기
   *
   * @param  [string] title
   * @param  [int] adult
   * @param  [string] kids : 아이들 나이 ,로 연결
   * @param  [string] attraction.*.place_id 
   * @param  [date] attraction.*.start_at 
   * @param  [date] attraction.*.end_at
   * @return [model] Travel 
   */
  public function create(Request $request)
  {
      try {
          $validator = Validator::make($request->all(), [
            'title' => 'required',
            'adult' => 'integer',
            'kids' => '',
            'attraction' => 'required|array',
            'image' => 'required',
            'attraction.*.place_id' => 'required',
            'attraction.*.start_at' => 'required|date',
            'attraction.*.end_at' => 'required|date|after_or_equal:attraction.*.start_at',
          ]);

          if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
          }

          $data = $request->all();
          $res = DB::transaction(function () use ($data, $request) {
              // 사용자 여행 생성
              $data['user_id'] = $request->user()->id;
              
              $travel = $this->travelRepository->create($data);

              // 여행지 추가
              foreach ($data['attraction'] as $value) {
                  // 여행지 정보 가져오기
                  $city = $this->cityRepository->getBy('place_id', $value['place_id']);      
                  // 사용자 여행지 추가
                  $newSet =[
                      'travel_id' => $travel->id,
                      'city_id' => $city->id,
                      'start_at' => $value['start_at'],
                      'end_at' => $value['end_at'],
                  ];
                  $att = $this->travelAttractionRepository->create($newSet);
              }

              $update_pr = [
                'start'=> $travel->attractions->min('start_at'),
                'end'=> $travel->attractions->max('end_at'),
              ];
              $this->travelRepository->updateById($travel->id, $update_pr);

              if ($travel) {
                  return $travel->id;
              }
 
              return $this->sendError(config('message.exception.TRAVEL_C_FAIL'), '');
          });
          
          if ($res) {
              $data = [
                  'travel' => $res,
              ];

              // loda 카드
              $res_todo = $this->createTodoLodaList($request->user(), $res);
              
              // 여행 중 loda 카드
              $this->makeTravelingCard($res);

              // user travel_cnt ++
              DB::table('users')->where('id', $request->user()->id)->increment('travel_cnt');

              if ($res_todo) {
                  return $this->sendResponse($data, '',201);
              }

              
          }
          
      } catch (GeneralException $e) {
          return $this->sendError(config('message.exception.TRAVEL_C_FAIL'), $e);
      } catch (OAuthServerException $e) { 
          return $this->sendError(config('message.exception.IVD_CRD'), '', 500);
      };
  }

  

  /**
   * 여행 수정하기
   *
   * @param  [string] title
   * @param  [int] adult
   * @param  [string] kids : 아이들 나이 ,로 연결
   * @param  [int] attraction.*.id 
   * @param  [string] attraction.*.place_id 
   * @param  [date] attraction.*.start_at 
   * @param  [date] attraction.*.end_at
   * @return [model] Travel 
   */
  public function update(Request $request, $travel_id)
  {
      
      $user = $request->user();
      try {
          $validator = Validator::make($request->all(), [
            'title' => 'max:255',
            'adult' => 'integer',
            'kids' => '',
            'attraction' => 'array',
            'attraction.*.id' => 'integer',
            'attraction.*.place_id' => 'required',
            'attraction.*.start_at' => 'required|date',
            'attraction.*.end_at' => 'required|date|after_or_equal:attraction.*.start_at',
          ]);
          
          if ($validator->fails()) {
              return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
          }

          $data = $request->all();
          $travel = $this->travelRepository->getBy('id', $travel_id);
          
          if ( !$travel || ($user->id != $travel->user_id)) {
              return $this->sendError(config('message.exception.IVD_REQ'), '');
          }

          $res = DB::transaction(function () use ($data, $travel_id, $user) {
              
              $travel = $this->travelRepository->getById($travel_id);
              $update_travel_info = [];

              if (array_key_exists('title', $data)) $update_travel_info['title'] = $data['title'];
              if (array_key_exists('adult', $data)) $update_travel_info['adult'] = $data['adult'];
              if (array_key_exists('kids', $data)) $update_travel_info['kids'] = $data['kids'];

              if (sizeof($update_travel_info)) {
                // 여행 수정
                $travel = $this->travelRepository->updateById($travel_id, $update_travel_info);
              }
              
              if (array_key_exists('attraction', $data)) {
              
                // 여행지 삭제
                $travel->attractions()->delete();

                // 여행지 추가
                foreach ($data['attraction'] as $key => $value) {
                  
                  // 여행지 정보 가져오기
                  $city = $this->cityRepository->getBy('place_id', $value['place_id']);

                  $newSet = [
                    'travel_id' => $travel->id,
                    'city_id' => $city->id,
                    'start_at' => $value['start_at'],
                    'end_at' => $value['end_at'],
                  ];

                  // 사용자 여행지 추가
                  $this->travelAttractionRepository->create($newSet);
                }

                // 여행 시작일, 종료일 수정
                $update_pr = [
                  'start' => $travel->attractions->min('start_at'),
                  'end' => $travel->attractions->max('end_at'),
                ];
                $this->travelRepository->updateById($travel->id, $update_pr);

                // 이 여행으로 생긴 로다 중에 예약 카드만 삭제
                $lodaList = $this->lodaRepository->where('travel_id', $travel_id)->get();
                foreach ($lodaList as $key => $loda) {
                  if ($loda->date > date('Y-m-d' . ' 59:59:59')) {
                    $this->lodaRepository->deleteRow($loda);
                  }
                }

                // loda 새로 생성
                $this->createTodoLodaList($user, $travel_id, 'edit');
                // 여행 중 loda 생성
                $this->makeTravelingCard($travel_id);
              }

              if ($travel) {
                return $travel->id;
              }

              return $this->sendError(config('message.exception.TRAVEL_U_FAIL'), '');
          });

          if ($res) {
            $data = [
              'travel' => $res,
            ];
            return $this->sendResponse($data, '', 204);
          }

      } catch (GeneralException $e) {
        return $this->sendError(config('message.exception.TRAVEL_C_FAIL'), $e);
      } catch (OAuthServerException $e) {
        return $this->sendError(config('message.exception.IVD_CRD'), '', 500);
      };
  }

  /**
   * 여행 목록
   *
   * @param  [string] status
   * @return [model] Travel 
   */
  public function list(Request $request)
  {
      $validator = Validator::make($request->all(), [
          'status' => 'starts_with:expected,gone',
          'type' => 'string',
      ]);

      if ($validator->fails()) {
          return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
      }

      $user = $request->user();
      $status = $request->only('status')['status']; 

      $travel_ids = $this->travelRepository->getByStart($user->id, $status);

      $travels = array();
      foreach ($travel_ids as $key => $value) {
          if (array_key_exists('type', $request->all())) {
              if ($request->only('type')['type'] == 'abbr') {
                  $travels[$key] = $this->perTravelAbbr($request, $value);
              } else {
                  $travels[$key] = $this->perTravel($request, $value);
              }
          } else {
              $travels[$key] = $this->perTravel($request, $value);
          }
      }

      return $this->sendResponse($travels);
  }

  /**
   * 여행 상세
   *
   * @param  [int] status
   * @return [model] Travel 
   */
  public function index(Request $request, $travel_id)
  {
      $data = $this->perTravel($request, $travel_id);
      return $this->sendResponse($data);
  }


  /**
   * 여행 상세 모델
   *
   * @param  [int] travel id
   * @return [model] Travel 
   */
  private function perTravel(Request $request, $travel_id)
  {
      $user = $request->user();
      $travel = $this->travelRepository->where('id', $travel_id)->get(['title', 'adult', 'kids', 'user_id', 'id', 'image', 'start', 'end', 'url'])->first();

      if (!$travel || ($user->id != $travel->user_id)) {
          throw new GeneralException(config('message.exception.IVD_REQ'));
      }

      $data = $travel;
      if (strlen($data['kids'])) {
          $data['kids'] = array_map('intval', explode(',', $data['kids']));
      } else {
          $data['kids'] = null;
      }
      unset($data['user_id']);

      $data['attractions'] = $travel->attractions->sortBy('start_at');
      
      foreach ($data['attractions'] as $key => $value) {
        $city = $this->cityRepository->where('id', $value['city_id'])->get(['name', 'name_en', 'country_id', 'place_id'])->first();
        $country = $this->countryRepository->where('id', $city['country_id'])->get(['name', 'name_en', 'alpha2Code', 'alpha3Code', 'flag'])->first();

        $value['city'] = $city;
        $value['city']['country'] = $country;
      }

      return $data;
  }

  /**
   * 여행 상세 모델
   *
   * @param  [int] travel id
   * @return [model] Travel 
   */
  private function perTravelAbbr(Request $request, $travel_id)
  {
      $user = $request->user();
      $travel = $this->travelRepository->where('id', $travel_id)->get(['title', 'start', 'end', 'user_id', 'id', 'image', 'adult', 'kids', 'url'])->first();
      $travel->kids = $travel->kids ? array_map('intval', explode(',', $travel->kids)) : null;

      if (!$travel || ($user->id != $travel->user_id)) {
        throw new GeneralException(config('message.exception.IVD_REQ'));
      }

      $data = $travel;
      $attractions = $travel->attractions->pluck(['city_id'])->all();
      $flags = [];

      foreach ($attractions as $value) {
        $city = $this->cityRepository->where('id', $value)->get(['country_id'])->first();
        $country = $this->countryRepository->where('id', $city['country_id'])->get(['flag'])->first();
        array_push($flags, $country->flag);
      }
      // 국기는 중복없이 3개까지만
      $data['flags'] = array_slice(array_unique($flags), 0, 3);

      unset($data['user_id']);
      unset($data['attractions']);

      return $data;
  }


  /**
   * 여행 삭제
   *
   * @param  [int] travel id
   */
  public function delete(Request $request, $travel_id)
  {
      $user = $request->user();
      $travel = $this->travelRepository->where('id', $travel_id)->get(['title', 'adult', 'kids', 'user_id', 'id'])->first();

      if (!$travel || ($user->id != $travel->user_id)) {
        return $this->sendError(config('message.exception.IVD_REQ'), 'no travel or no user permissions');
      }
      $res = false;
      try {
          $res = DB::transaction(function () use ($travel_id, $user) {
              $ids_val = $this->travelAttractionRepository->getListByColumn($travel_id, 'travel_id', ['id']);

              $ids = [];
              foreach ($ids_val as $key => $value) {
                array_push($ids, $value->id);
              }
              // 등록 여행지 삭제
              $this->travelAttractionRepository->deleteMultipleById($ids);

              // 로다 삭제
              $loda = $this->lodaRepository->where('travel_id', $travel_id)->get();
              $loda->each(function ($item) {
                $this->lodaRepository->deleteRow($item);
              });
              

              // 할일 삭제
              $this->travelTodoRepository->where('travel_id', $travel_id)->delete();

              // 패킹 삭제
              DB::table('travel_packings_categories')->where('travel_id', $travel_id)->delete();

              // 노트 삭제
              DB::table('notes')->where('travel_id', $travel_id)->delete();

              // 여행 삭제
              $this->travelRepository->deleteById($travel_id);

              // user travel_cnt ++
              DB::table('users')->where('id', $user->id)->decrement('travel_cnt');
              return true;
          });
      } catch (\Throwable $th) {
          return $this->sendError(config('message.exception.SRV_ERR'), 'accrue error deleting travel');
      }
      

      if ($res == true) {
          return $this->sendResponse('', '', 204);
      }

      return $this->sendError(config('message.exception.SRV_ERR'), '');
  }

  /**
   * 여행 이미지 업데이트
   *
   * @param  [file] travel image
   */
  public function picture(Request $request, $travel_id)
  {
    $user = $request->user();
    $travel = $this->travelRepository->where('id', $travel_id)->first();

    if (!$travel || ($user->id != $travel->user_id)) {
      return $this->sendError(config('message.exception.IVD_REQ'), 'no travel or no user permissions');
    }
    
    $validator = Validator::make($request->all(), [
      'image' => 'image|max:'. config('services.file_limit.size'),
    ]);

    if ($validator->fails()) {
      return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
    }

    $user = $request->user();
    $input = $request->all();
    $file = $input['image'];
    $name = time() . $file->getClientOriginalName();
    $filePath = 'user/travel/'. $user->id. '/' . $name;
    Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');

    // 기존 이미지 삭제
    if ($travel->image) {
      if (strpos($travel->image, 'user/travel')) {
        Storage::disk('s3')->delete(substr($travel->image, 1));
      }
    }

    // 새 이미지
    $input['image'] = '/'.$filePath;
    $this->travelRepository->updateById($travel_id, $input);

    $success['image'] = '/' . $filePath;
    return $this->sendResponse($success);
  }



  /**
   * 여행 이미지 업데이트
   *
   * @param  [file] travel image
   */
  public function getUrl(Request $request, $travel_id)
  {
    $travel = $this->travelRepository->getById($travel_id);
    if (! $travel->url) {
      $st = \substr(env('APP_KEY'), 3, 8) . $travel_id;
      $en_st = env('APP_ADMIN_URL').'/timeline?key='.Crypt::encryptString($st);
      $en_st_res = shortUrl($en_st);
      if ($en_st_res) {
        $travel->url = $en_st_res;
        $travel->save();
      } else {
        return $this->sendError(config('message.exception.SRV_ERR'), 'short url create failed');
      }
    }
    
    if ($travel->url) {
      $res = [
        'url' => $travel->url
      ];
      return $this->sendResponse($res);
    }
    
  }


  private function createTodoLodaList($user, $travel_id, $status = 'new')
  {
    $base_cards = '';
    if ($status == 'edit') { // start card
      $base_cards = $this->cardsRepository->where('scheduled', 1)
          ->where('type', 'start', '!=')
          ->orderBy('id', 'DESC')->get();
    } else {
      $base_cards = $this->cardsRepository->where('scheduled', 1)
        ->orderBy('id', 'DESC')->get();
    }
  
    // << 여행생성 or 여행수정
    $datePast = false;
    $travel = $this->travelRepository->getById($travel_id);
    $push_keys = array();
    foreach ($base_cards as $item) {
      $endpoint_ids = array();
      
      DB::beginTransaction();

      if ( $item->individual == 1) {
        // $item->individual == 1 >> weather, info
        
        foreach ($travel->attractions as $tatt) {
          array_push($endpoint_ids, $tatt->city_id);
        }
      } else if ($item->type == 'todo') {
        
        // 여행 생성 시에 할일 목록 만들기
        if ( $status == 'new') {
          try {
            
            // todo item careate
            $param = [
              'travel_id' => $travel_id,
              'todo_id' => $item->parent_id,
              'checked' => config('services.tool_checked.unchecked')
            ];
            $newTravelTodo = $this->travelTodoRepository->create($param);
            array_push($endpoint_ids, $newTravelTodo->id);
          } catch (\Throwable $th) {
            DB::rollback();
            return $this->sendError(config('message.exception.SRV_ERR'), 'fail make todo list');
          }
        } else {
          if ($item->id != config('services.loda_card_id.visa') && $item->id != config('services.loda_card_id.passport')) {
            try {
              // travel_todo에서 기존 것을 가져와야 함!
              $travel_todo = $this->travelTodoRepository->getByTravelandTodo($travel_id, $item->parent_id);
              if (!$travel_todo) {
                $param = [
                  'travel_id' => $travel_id,
                  'todo_id' => $item->parent_id,
                  'checked' => config('services.tool_checked.unchecked')
                ];
                $travel_todo = $this->travelTodoRepository->create($param);
              } 
              array_push($endpoint_ids, $travel_todo->id);
            } catch (\Throwable $th) {
              DB::rollback();
              return $this->sendError(config('message.exception.SRV_ERR'), 'fail make todo list');
            }
          }
        }
      } else {
        // start, dday
        array_push($endpoint_ids, $travel_id);
      }

      try {
        // 발송일
        
        if (env('APP_ENV') == 'release') { // APP_ENV == release
          $timestamp = strtotime(date('Y-m-d H:i:s') . ' +' . abs($item->dday) . ' minutes');
          $recive_date = date( 'Y-m-d H:i:s', $timestamp);
        } else { // APP_ENV == product
          $dday = $item->dday > 0 ? ' +' . $item->dday : ' ' . $item->dday;
          $timestamp = strtotime($travel->start . ' ' . $dday . ' days');
          $time = $item->time? $item->time: config('services.push.time');
          $recive_date = date('Y-m-d ' . $time, $timestamp);
        }

        // start card type
        if ($item->type === config('services.loda_card.start')) {
          $recive_date = date('Y-m-d H:i:s', strtotime('+3 minutes'));
          if ( $travel->start < date('Y-m-d', strtotime('+5 days'))) {
            // 여행시작일이 5일도 안남았을 때
            $item->title = config('services.start_card.last_title');
            $item->contents = config('services.start_card.last_contents');
          } elseif ($datePast) {
            // todo 카드를 하나라도 못 받을 때
            $item->title = config('services.start_card.miss_title');
            $item->contents = config('services.start_card.miss_contents');
          }
        } elseif ( $item->id == config('services.loda_card_id.visa')) {
          $recive_date = date('Y-m-d H:i:s', strtotime('+20 minutes'));
        } elseif ( $item->id == config('services.loda_card_id.passport')) {
          $recive_date = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        }
      } catch (\Throwable $th) {
        return $this->sendError(config('message.exception.SRV_ERR'), 'fail to make recive time');
      }

      if (env('APP_ENV') == 'release') { // APP_ENV == release
        if (
          $recive_date > date('Y-m-d 00:00:00') || $item->type == config('services.loda_card.start')
          || $item->id == config('services.loda_card_id.visa') || $item->id == config('services.loda_card_id.passport')
        ) {
          try {

            foreach ($endpoint_ids as $eid) {

              $loda_set = [
                'user_id' => $user->id,
                'travel_id' => $travel_id,
                'card_id' => $item->id,
                'date' => $recive_date,
                'endpoint_id' => $eid
              ];

              // loda insert
              $loda = $this->lodaRepository->create($loda_set);

              // pusu direct << release test
              $res_push = createPush($item->title, $item->contents, array_merge($item->toArray(), $loda->toArray()), [$user->id], $recive_date);
              $res_push_arr = json_decode($res_push, true);

              if (\array_key_exists('id', $res_push_arr)) {
                array_push($push_keys, $res_push_arr['id']);
                $up_set = [
                  'onesignal_id' => $res_push_arr['id']
                ];
                $this->lodaRepository->updateById($loda->id, $up_set);
                Log::info('* onesignal push success');
              } else {
                Log::info('onesignal push error: ' . $res_push);
              };
            }
          } catch (\Throwable $th) {
            DB::rollback();
            // 보냈던 푸시 메시지는 다 취소
            foreach ($push_keys as $value) {
              cancelPush($value);
            }
            return $this->sendError(config('message.exception.SRV_ERR'), 'fail make loda card list');
          }
        } else {
          $datePast = true;
        }
      } else { // APP_ENV == product
        // push 발송 시간이 지금 이후인 경우만
        if (
          $recive_date > date('Y-m-d 23:59:59') || $item->type == config('services.loda_card.start')
          || $item->id == config('services.loda_card_id.visa') || $item->id == config('services.loda_card_id.passport')
        ) {
          try {

            foreach ($endpoint_ids as $eid) {

              $loda_set = [
                'user_id' => $user->id,
                'travel_id' => $travel_id,
                'card_id' => $item->id,
                'date' => $recive_date,
                'endpoint_id' => $eid
              ];

              // loda insert
              $loda = $this->lodaRepository->create($loda_set);

              // < start push insert #20190705 push는 데일리 cron으로 
              if (
                $item->type == config('services.loda_card.start') ||
                $item->id == config('services.loda_card_id.visa') ||
                $item->id == config('services.loda_card_id.passport')
              ) {
                $res_push = createPush($item->title, $item->contents, array_merge($item->toArray(), $loda->toArray()), [$user->id], $recive_date);
                $res_push_arr = json_decode($res_push, true);

                if (\array_key_exists('id', $res_push_arr)) {
                  array_push($push_keys, $res_push_arr['id']);
                  $up_set = [
                    'onesignal_id' => $res_push_arr['id']
                  ];
                  $this->lodaRepository->updateById($loda->id, $up_set);
                  Log::info('* onesignal push success');
                } else {
                  Log::info('onesignal push error: ' . $res_push);
                };
              }
              // > push insert
            }
          } catch (\Throwable $th) {
            DB::rollback();
            // 보냈던 푸시 메시지는 다 취소
            foreach ($push_keys as $value) {
              cancelPush($value);
            }
            return $this->sendError(config('message.exception.SRV_ERR'), 'fail make loda card list');
          }
        } else {
          $datePast = true;
        }
      }

      DB::commit();
      }
    return true;
  }

  private function makeTravelingCard($travel_id) 
  {
    $travel = $this->travelRepository->getById($travel_id);
    $travelingCards = $this->cardsRepository->getByTypeIn(['ing']);
    $today = $travel->start;
    $days = 0;

    DB::beginTransaction();
    try {
      foreach ($travelingCards as $card) {
        // 기존 여행 중 카드 삭제
        $this->lodaRepository->where('travel_id', $travel_id)->where('card_id', $card->id)->delete();
        // exit;
        while ($travel->end >= $today) {
          $timestamp = strtotime($travel->start . ' +' . $days . ' days');
          $today = date('Y-m-d ', $timestamp);
          $city_id = $this->cityOfTheDay($travel_id, $today);
          $utc_date = $this->dateAtTimezone($today . ' ' . $card->time, $city_id);
          $loda_set = [
            'user_id' => $travel->user_id,
            'travel_id' => $travel_id,
            'card_id' => $card->id,
            'date' => $utc_date,
            'endpoint_id' => $city_id
          ];

          // loda insert
          $this->lodaRepository->create($loda_set);
          // next
          $days++;
        }
      }
    } catch (\Throwable $th) {
      DB::rollback();
    }
    
    DB::commit();
  }

  private function cityOfTheDay($travel_id, $date)
  {
      $theCity = '4086';
      $att = $this->travelAttractionRepository->orderBy('start_at')->getListByColumn($travel_id, 'travel_id');
      foreach ($att as $v) {
        if ($v->start_at <= $date && $v->end_at > $date) {
          $theCity = $v->city_id;
        }
      }
      return $theCity;
  }

  private function dateAtTimezone($date, $city_id) 
  {
    $city = $this->cityRepository->getById($city_id);
    $timezoen = DB::table('countries_timezone')
        ->join('timezones', 'countries_timezone.timezone_id', '=', 'timezones.id')
        ->where('country_id', $city->country_id)
        ->first(['code']);
    $timezoneDiff = (int) substr($timezoen->code, 3, 3) - 9;
    
    return date('Y-m-d H:i:s', strtotime($timezoneDiff . " hours", strtotime($date)));
  }

}
