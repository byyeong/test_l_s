<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // 
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            Log::info( '[cron] ********** daily city weather');

            // 날씨 카드
            $weather_card = DB::table('cards')->where('type', 'weather')->get(['id']);
            $date_today = date('Y-m-d');
            $date_yesterday = date('Y-m-d', strtotime('-1 days'));
            // 날씨 카드 ids
            $cards = $weather_card->map(function ($item) {
                return $item->id;
            });
            // 오늘 나가는 날씨  
            $weather_loda = DB::table('loda')
                ->join('cities', 'cities.id', '=', 'loda.endpoint_id')
                ->where('date', 'like', $date_today . '%')
                ->whereIn('card_id', $cards)
                ->groupBy('endpoint_id')
                ->get(['lat', 'lng', 'endpoint_id', 'travel_id', 'onesignal_id', 'loda.id', 'card_id']);
            // 오늘 나가는 날씨 카드의 도시들 날씨 수집
            foreach ($weather_loda as $val) {
                $weather = getWeatherFromOpenweather($val->lat, $val->lng);
                if ($weather) {
                    if (\array_key_exists('list', $weather)) {
                        foreach ($weather['list'] as $value) {

                            try {
                                $icon = count($value['weather'])? $value['weather'][0]['icon']: '';
                                $set = [
                                    'city_id' => $val->endpoint_id,
                                    'date' => date('Y-m-d', $value['dt']),
                                    'temp_max' => $value['temp']['max'],
                                    'temp_min' => $value['temp']['min'],
                                    'icon' => $icon,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ];
                                DB::table('cities_weather')->insert($set);
                            } catch (\Throwable $th) {
                                Log::notice( 'error insert city weather : '.json_encode($value));
                            }
                        }
                    }
                } else {
                    Log::notice('fail get weather. city_id : '.$val->endpoint_id);
                }
            }
            // 오늘 나가는 로다 날씨 카드 
            $today_cards = DB::table('loda')
                ->join('cities', 'cities.id', '=', 'loda.endpoint_id')
                ->where('date', 'like', $date_today . '%')
                ->whereIn('card_id', $cards)
                ->get(['lat', 'lng', 'endpoint_id', 'travel_id', 'onesignal_id', 'loda.id', 'card_id']);

            // 어제의 날씨와 비교 후 동일한 데이터는 삭제
            foreach ($today_cards as $v) {
                if ($v->card_id != config('services.loda_card_id.weather_first')) { // 첫날 카드는 비교 안함
                    $travel = DB::table('travel')->where('id', $v->travel_id)->first();
                    if ($travel) {
                        $today = getWeatherByCityAndDate($date_today, $v->endpoint_id, $travel->start, $travel->end);
                        $yesterday = getWeatherByCityAndDate($date_yesterday, $v->endpoint_id, $travel->start, $travel->end);
                        $point = 0;
                        if ($yesterday->count() != 0) {
                            foreach ($today as $key => $item) {
                                $point = 0;
                                if (!array_key_exists($key, $yesterday->toArray())) {
                                    $point++;
                                } else {
                                    if (
                                        ($item->icon == $yesterday[$key]->icon)
                                        && (abs($item->temp_max - $yesterday[$key]->temp_max) < 3)
                                        && (abs($item->temp_min - $yesterday[$key]->temp_min) < 3)
                                    ) {
                                        // 날씨 변화 없는 날
                                    } else {
                                        // 날씨 변화 있는 날
                                        $point++;
                                        DB::table('cities_weather')->where('id', $item->id)
                                            ->update(['mark' => 1]);
                                    }
                                }
                            }
                        } else {
                            $point++;
                        }
                        if ($point == 0) {
                            if ($v->onesignal_id) {
                                cancelPush($v->onesignal_id);
                            }
                            DB::table('loda')->delete($v->id);
                            Log::notice('same weather from yesterday. loda.id : ' . $v->id);
                        }
                    } else {
                        if ($v->onesignal_id) {
                            cancelPush($v->onesignal_id);
                        }
                        DB::table('loda')->delete($v->id);
                        Log::notice('removed travel. loda.id : ' . $v->id);
                    }
                }
            }

            Log::info('[cron] ********** traveling city weather');
            // 오늘 여행 중인 사람이 있으면 여행중인 도시의 오늘 날씨 저장
            $ing_travel = DB::table('travel_attraction')
                ->join('cities', 'cities.id', '=', 'travel_attraction.city_id')
                ->where('start_at', '<=', date('Y-m-d'))
                ->where('end_at', '>', date('Y-m-d'))
                ->distinct()
                ->get(['cities.id', 'lat', 'lng']);
            foreach ($ing_travel as $i) {
                $forcast = getWeatherFromOpenweather($i->lat, $i->lng, 2);
                if ($forcast) {
                    if (\array_key_exists('list', $forcast)) {
                        foreach ($forcast['list'] as $v) {
                            $ch_w = DB::table('cities_weather')
                                ->where('city_id', $i->id)
                                ->where('date', date('Y-m-d', $v['dt']))
                                ->where('created_at', 'like', date('Y-m-d') . '%')
                                ->first();
                            if (!$ch_w) {
                                try {
                                    $icon = count($v['weather']) ? $v['weather'][0]['icon'] : '';
                                    $set = [
                                        'city_id' => $i->id,
                                        'date' => date('Y-m-d', $v['dt']),
                                        'temp_max' => $v['temp']['max'],
                                        'temp_min' => $v['temp']['min'],
                                        'icon' => $icon,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ];
                                    DB::table('cities_weather')->insert($set);
                                } catch (\Throwable $th) {
                                    Log::notice('error insert city weather : ' . json_encode($v));
                                }
                            }
                        }
                    }
                }
            }
        })->dailyAt('00:05');
        // });


        $schedule->call(function () {
            Log::info('[cron] ********** 10minutes push');
            // 1day push
            $pre = DB::table('loda')
                ->where('date', '>=', now())
                ->where('date', '<=', date('Y-m-d H:i:s', strtotime('+20 minutes')))
                ->where('onesignal_id', '')
                ->where('deleted_at', null)
                ->get();
            foreach ($pre as $value) {
                try {
                    $item = DB::table('cards')->where('id', $value->card_id)->first();
                    if ($item->type == 'weather') {
                        $travel = DB::table('travel')->where('id', $value->travel_id)->first();
                        $weather = getWeatherByCityAndDate(
                            substr($value->date, 0, 10),
                            $value->endpoint_id,
                            $travel->start,
                            $travel->end
                        );
                        if (!$weather->count()) {
                            DB::table('loda')
                                ->where('id', $value->id)
                                ->update(['deleted_at' => now()]);
                            Log::notice('no Weather city & loda : ' . $value->endpoint_id . '/' . $value->id);
                            continue;
                        }
                    }
                    if ($item->type == 'info') {
                        // info
                        $city = DB::table('cities')->where('id', $value->endpoint_id)->first();
                        // 비행시간, 통화, 언어, 전압 정보 있어야 카드 보냄.
                        if (!$city->time || !$city->currency_id || !$city->language_id || !$city->voltage) {
                            DB::table('loda')
                                ->where('id', $value->id)
                                ->update(['deleted_at' => now()]);
                            Log::notice('no Info city & loda : ' . $value->endpoint_id . '/' . $value->id);
                            continue;
                        }
                    }
                    if ($item->type === 'goods' ||  $item->type === 'any') {
                        $item->title = $item->push_title ? $item->push_title : $item->title;
                        $item->contents = $item->push_contents ? $item->push_contents : $item->contents;
                    } elseif ($item->type === 'ing') {
                        $item->title = config('services.traveling_loda_card_push_comments.title');
                        $item->contents = config('services.traveling_loda_card_push_comments.contents');
                    }

                    $res_push = createPush($item->title, $item->contents, array_merge(json_decode(json_encode($item), true), json_decode(json_encode($value), true)), [$value->user_id], $value->date);
                    $res_push_arr = json_decode($res_push, true);
                    $msg = '';
                    if (\array_key_exists('id', $res_push_arr)) {
                        if ($res_push_arr['id'] != '') {
                            $msg = $res_push_arr['id'];
                            Log::info('[cron] onesignal push success');
                        } else {
                            $msg = 'errors-' . (string) $res_push_arr['errors'];
                            Log::notice('[cron] onesignal push error: ' . $res_push);
                        }
                    } else {
                        $msg = 'errors-' . (string) $res_push_arr['errors'];
                        Log::notice('[cron] onesignal push error: ' . $res_push);
                    };
                    $up_set = [
                        'onesignal_id' => $msg
                    ];
                    DB::table('loda')
                        ->where('id', $value->id)
                        ->update($up_set);
                } catch (\Throwable $th) {
                    Log::notice('[cron] onesignal error loda id : ' . $value->id);
                }
            }
        })->cron('*/10 * * * *');

    }
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
