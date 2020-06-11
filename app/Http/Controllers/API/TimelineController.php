<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\BaseController;
use App\Repositories\API\TravelRepository;
use App\Repositories\API\TravelAttractionRepository;
use App\Repositories\API\DiaryRepository;
use App\Repositories\API\WalletRepository;
use App\Repositories\API\NotesRepository;
use App\Repositories\API\CurrencyRepository;
use App\Repositories\API\CountryRepository;
use App\Repositories\API\CityWeatherRepository;

class TimelineController extends BaseController
{
    /**
     * @var TravelRepository
     * @var TravelAttractionRepository
     * @var DiaryRepository
     * @var WalletRepository
     * @var NotesRepository
     * @var CurrencyRepository
     * @var CountryRepository
     * @var CityWeatherRepository
     */
    protected $travelRepository;
    protected $travelAttractionRepository;
    protected $diaryRepository;
    protected $walletRepository;
    protected $notesRepository;
    protected $currencyRepository;
    protected $countryRepository;
    protected $cityWeatherRepository;

    /**
     * @param TravelRepository $travelRepository
     * @param TravelAttractionRepository $travelAttractionRepository
     * @param DiaryRepository $diaryRepository
     * @param WalletRepository $walletRepository
     * @param NotesRepository $notesRepository
     * @param CurrencyRepository $currencyRepository
     * @param CountryRepository $countryRepository
     * @param CityWeatherRepository $cityWeatherRepository
     */
    public function __construct(TravelRepository $travelRepository,
        TravelAttractionRepository $travelAttractionRepository,
        DiaryRepository $diaryRepository,
        WalletRepository $walletRepository,
        NotesRepository $notesRepository,
        CurrencyRepository $currencyRepository,
        CountryRepository $countryRepository,
        CityWeatherRepository $cityWeatherRepository
    ) 
    {
        $this->travelRepository = $travelRepository;
        $this->travelAttractionRepository = $travelAttractionRepository;
        $this->diaryRepository = $diaryRepository;
        $this->walletRepository = $walletRepository;
        $this->notesRepository = $notesRepository;
        $this->currencyRepository = $currencyRepository;
        $this->countryRepository = $countryRepository;
        $this->cityWeatherRepository = $cityWeatherRepository;
    }

    /**
     * Display a listing of the resource.
     * 지나간 여행 타임라인
     *
     * @param  int  $travel_id
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $key = Input::get('key');
        $key_res = '';
        
        try {
            $key_res = Crypt::decryptString($key);
        } catch (\Throwable $th) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'invalid key');
        }

        if (\substr($key_res, 0, 8) !== \substr(env('APP_KEY'), 3, 8)) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no permission');
        }

        $key_res = \substr($key_res, 8);
        $res = '';
        $travel = $this->travelRepository->getById($key_res);
        $user = DB::table('users')->where('id', $travel->user_id)->first(['name', 'image']);
        $dialy = $this->diaryRepository->orderBy('date')->with('files')->getListByColumn($key_res, 'travel_id');
        $wallet = $this->walletRepository->orderBy('date')->with('files')->getListByColumn($key_res, 'travel_id');
        $note = $this->notesRepository->orderBy('id')->getListByColumn($key_res, 'travel_id');

        $country_id = [];
        $timeline = [
            'pre' => [
                'data' => []
            ],
            'after' => [
                'data' => []
            ]
        ];
        $wallet_total = 0;

        foreach ($travel->attractions as $c) {
            \array_push($country_id, $c->city->country_id);
        }
        $countries = $this->countryRepository->whereIn('id', $country_id)->get();

        $currencies = travelCurrencyList($key_res);

        foreach ($currencies as $c) {
            $val = $c;
            $val->exc = $this->currencyRepository->getExchange($key_res, $val->id);
        }

        $date = $travel->start;
        while ($date <= $travel->end) {
            $timeline[$date]['cities'] = \getCitiesOfTheDay($date, $travel);
            
            foreach ($timeline[$date]['cities'] as $cw) {
                $cw->weather = $this->cityWeatherRepository->getByDateCity($date, $cw->id);
            }
            $timeline[$date]['data'] = [];
            $date = date('Y-m-d', strtotime($date . " +1 days"));
        }

        foreach ($dialy as $v) {
            $d = \substr($v->date, 0, 10);
            if ($travel->start <= $d && $travel->end >= $d) {
                \array_push($timeline[$d]['data'], $v);
            } elseif ($travel->start > $d) {
                \array_push($timeline['pre']['data'], $v);
            } elseif ($travel->end < $d) {
                \array_push($timeline['after']['data'], $v);
            }
            
        }

        foreach ($wallet as $w) {
            $d = \substr($w->date, 0, 10);
            if ($travel->start <= $d && $travel->end >= $d) {
                \array_push($timeline[$d]['data'], $w);
            } elseif ($travel->start > $d) {
                \array_push($timeline['pre']['data'], $w);
            } elseif ($travel->end < $d) {
                \array_push($timeline['after']['data'], $w);
            }
            $w->currency = $this->currencyRepository->getById($w->currency_id);
            if ($w->currency_id == config('services.currency_rep.KRW')) {
                $wallet_total = $wallet_total + $w->price;
            } else {
                $exc = $this->currencyRepository->getExchange($key_res, $w->currency_id);
                if (! $exc) $exc = $this->currencyRepository->lately_currency($w->currency_id);
                $wallet_total = $wallet_total + ($w->price * $exc->amount);
            }
        }

        foreach ($timeline as $tkey => $tt) {
            if (\sizeof($tt['data'])) {
                $new = $timeline[$tkey]['data'];
                $keys = array_column($new, 'date');
                array_multisort($keys, SORT_ASC, $new);
                $timeline[$tkey]['data'] = $new;
            } 
        }

        // foreach ($note as $n) {
        //     $d = \substr($n->created_at, 0, 10);
        //     if ($travel->start <= $d && $travel->end >= $d) {
        //         \array_push($timeline[$d]['data'], $n);
        //     } elseif ($travel->start > $d) {
        //         \array_push($timeline['pre']['data'], $n);
        //     }
        // };

        $res = [
            'travel' => $travel,
            'user' => $user,
            'dialy_cnt' => $dialy->count(), 
            'wallet_total' => $wallet->count(),
            'note_cnt' => $note->count(),
            'countries' => $countries,
            'timeline' => $timeline,
            'wallet_total' => $wallet_total
        ];

        return $this->sendResponse($res, '');
    }
}
