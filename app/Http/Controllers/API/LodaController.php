<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\BaseController;
use App\Repositories\API\LodaRepository;
use App\Repositories\API\TravelTodoRepository;
use App\Repositories\API\CityRepository;
use App\Repositories\API\TravelPackingsCategoriesRepository;
use App\Repositories\API\TravelRepository;
use App\Repositories\API\LodaExtRepository;
use App\Repositories\API\TravelAttractionRepository;
use App\Repositories\API\CityWeatherRepository;
use function GuzzleHttp\json_decode;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LodaController extends BaseController
{
    /**
     * @var LodaRepository
     * @var TravelTodoRepository
     * @var CityRepository
     * @var TravelPackingsCategoriesRepository
     * @var TravelRepository
     * @var LodaExtRepository
     * @var TravelAttractionRepository
     * @var CityWeatherRepository
     */
    protected $lodaRepository;
    protected $travelTodoRepository;
    protected $cityRepository;
    protected $travelPackingsCategoriesRepository;
    protected $travelRepository;
    protected $lodaExtRepository;
    protected $travelAttractionRepository;
    protected $cityWeatherRepository;

    /**
     * @param LodaRepository $lodaRepository
     * @param TravelTodoRepository $travelTodoRepository
     * @param CityRepository $cityRepository
     * @param TravelPackingsCategoriesRepository $travelPackingsCategoriesRepository
     * @param TravelRepository $travelRepository
     * @param LodaExtRepository $lodaExtRepository
     * @param TravelAttractionRepository $travelAttractionRepository
     * @param CityWeatherRepository $cityWeatherRepository
     */
    public function __construct(LodaRepository $lodaRepository, TravelTodoRepository $travelTodoRepository,
            CityRepository $cityRepository, TravelPackingsCategoriesRepository $travelPackingsCategoriesRepository,
            TravelRepository $travelRepository, LodaExtRepository $lodaExtRepository,
            TravelAttractionRepository $travelAttractionRepository,
            CityWeatherRepository $cityWeatherRepository)
    {
        $this->lodaRepository = $lodaRepository;
        $this->travelTodoRepository = $travelTodoRepository;
        $this->cityRepository = $cityRepository; 
        $this->travelPackingsCategoriesRepository = $travelPackingsCategoriesRepository;
        $this->travelRepository = $travelRepository;
        $this->lodaExtRepository = $lodaExtRepository;
        $this->travelAttractionRepository = $travelAttractionRepository;
        $this->cityWeatherRepository = $cityWeatherRepository;
    }

    /**
     * App config
     *
     * @return \App\Models\Loda
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // 오늘까지의 로다
        $until = Input::get('until');
        if ( !$until) $until = date('Y-m-d H:i:s');
        $res = $this->lodaRepository->getByUserUntilDate($user->id, $until);

        foreach ($res as $key => $value) {
            if ( $value->travel_id) {
                $t_v = $this->travelRepository->where('id', $value->travel_id)->first(['start', 'end', 'title', 'id', 'image', 'adult', 'kids'])->toArray();
                $t_v['flags'] = getFlagAtTravel($value->travel_id);
                $t_v['kids'] = $t_v['kids']? array_map('intval', explode(',', $t_v['kids'])): null;
                $value['data'] = [
                    'travel' => $t_v
                ];
            }
            if ($value->card) {
                $value->card->theday = $value->card->dday;
                $ingInfo = [];
                switch ($value->card->type) {
                    case 'todo':
                        $t_todo = $this->travelTodoRepository->getWithTodo($value['endpoint_id']);
                        $set = [
                            'title' => $t_todo->todo->title,
                            'travel_todo_id' => $value['endpoint_id'],
                            'checked' => $t_todo->checked 
                        ];
                        $value['data'] = array_merge( $value['data'], $set);
                        if ($value->card) {
                            if (\substr($value->card->target, 0, 1) == '/') {
                                $value->card->target = env('APP_ADMIN_URL') . $value->card->target;
                            }
                        }
                        break;
                    case 'weather':
                        $city = $this->cityRepository->getById($value['endpoint_id'], ['name', 'name_en']);
                        $attr = $this->travelAttractionRepository->where('travel_id', $value->travel_id)->where('city_id', $value['endpoint_id'])->get();
                        if ($attr->count()) {
                            $at = $attr[0];
                            $weather = getWeatherByCityAndDate(
                                substr($value['date'], 0, 10),
                                $value['endpoint_id'],
                                $at->start_at,
                                $at->end_at
                            );
                            if ($weather->count()) {
                                $weather = [
                                    'weather' => $weather->toArray()
                                ];
                                $value['data'] = array_merge($value['data'], $weather);
                                $value['data'] = array_merge($value['data'], $city->toArray());
                            } else {
                                $value->id = 0; // 날씨 수집 없음
                            }
                            
                        } else {
                            $value->id = 0; // 여행지 정보 없음
                        }
                        break;
                    case 'info':
                        $city = $this->cityRepository->getById($value['endpoint_id']);
                        $city->country;
                        $city->time = $city->time != '00:00:00' ? $city->time : NULL;
                        $city->time_difference = $city->edited ? $city->time_difference : NULL;
                        $city->voltage = $city->voltage ? $city->voltage : NULL;

                        $value['data'] = array_merge($value['data'], $city->toArray());

                        $lang = '';
                        if ($city->language_id) {
                            $lang = [
                                'language' => $this->cityRepository->getLanguage($city->language_id)
                            ];
                        } else {
                            $lang = [
                                'language' => $this->cityRepository->getLanguageByCountry($city->country_id)
                            ];
                        }
                        $value['data'] = array_merge($value['data'], $lang);

                        $curr = '';
                        if ($city->currency_id) {
                            $curr = [
                                'currency' => $this->cityRepository->getCurrency($city->currency_id)
                            ];
                        } else {
                            $curr = [
                                'currency' => $this->cityRepository->getCurrencyByCountry($city->country_id)
                            ];
                        }
                        
                        $value['data'] = array_merge($value['data'], $curr);
                        break;
                    case 'dday':
                        $ext = $value->ext;
                        $set = '';
                        if ($ext) {
                            $set = json_decode($ext->data);
                        } else {
                            $todo = $this->travelTodoRepository->getListByColumn($value['endpoint_id'], 'travel_id');
                            $todo_checked = $this->travelTodoRepository
                                ->getListChecked($value['endpoint_id'], config('services.tool_checked.checked'));
                            $packing = $this->travelPackingsCategoriesRepository
                                ->getOnlyPackingByTravel($value['endpoint_id'], config('services.tool_show.show'));
                            $packing_checked = $this->travelPackingsCategoriesRepository
                                ->getOnlyPackingByTravel($value['endpoint_id'], config('services.tool_show.show'), config('services.tool_checked.checked'));
                            $set = [
                                'todo' => $todo->count(),
                                'todo_checked' => $todo_checked->count(),
                                'packing' => $packing->count(),
                                'packing_checked' => $packing_checked->count()
                            ];
                            $this->lodaExtRepository->create([
                                'loda_id' => $value->id,
                                'data' => json_encode($set)
                            ]);
                        }
                        $extData = [
                            'ext' => $set
                        ];
                        
                        $value['data'] = array_merge( $value['data'], $extData);
                        if ($value['ext']) {
                            unset($value['ext']);
                        }
                        
                        break;
                    case 'ing':
                        $ingInfo = getIngInfo($value['data']['travel'], $value['date']);
                        $value['data'] = array_merge($value['data'], $ingInfo);
                        break;
                }
                
            }
            

            if ($value->id) {
                if ($value->card) {
                    $value->card->dday = abs($value->card->theday);
                    if ($value->card->id == 5 || $value->card->id == 9) {
                        $value->card->dday = 31;
                        $value->card->theday = -31;
                    } else if ($value->card->id == 31) {
                        $value->card->dday = 30;
                        $value->card->theday = -30;
                    } else if ($value->card->id == 49) {
                        $value->card->dday = 1;
                        $value->card->theday = -1;
                    } else if ($value->card->id == 30) {
                        $value->card->dday = 15;
                        $value->card->theday = -15;
                    } else if ($value->card->id == 29) {
                        $value->card->dday = 7;
                        $value->card->theday = -7;
                    } else if ($value->card->id == 49) {
                        $value->card->dday = 1;
                        $value->card->theday = -1;
                    } else if ($value->card->dday == 0) {
                        $value->card->dday = 0;
                        $value->card->theday = 0;
                    }
                }
                
            } else {
                unset($res[$key]);
            }
        }

        // unset 이 쓰이면 데이터 포맷이 바뀜 ㅠㅠ
        $data = $res->filter()->values();
        $result = $res->toArray();
        unset($result['data']);
        $result['data'] = $data;

        return $this->sendResponse($result, '');
    }

    public function delete($loda_id)
    {
        $loda = $this->lodaRepository->getById($loda_id);

        if ($loda->user_id != request()->user()->id) {
            return $this->sendError(config('message.exception.NO_PEM_TRV_EDIT'), 'no permission for delete');
        }

        $this->lodaRepository->deleteById($loda_id);
    }
}