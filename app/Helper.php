<?php

    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\DB;
    use DateTime;
    use App\Repositories\API\CurrencyRepository;
    use App\Repositories\API\TravelAttractionRepository;
    use App\Repositories\API\CountryRepository;

    /**
     * get google place data
     *
     * @param String place_id, String language
     * @param Json
     */

    function cityDetailBygoogle($place_id, $language)
    {
        $ch = curl_init();

        $url = env('GOOGLE_PLACE_API'); // URL
        $queryParams = '?' . urlencode('key') . '=' . env('GOOGLE_PLACE_API_KEY'); // Service Key
        $queryParams .= '&' . urlencode('placeid') . '=' . $place_id; // 파라미터설명
        $queryParams .= '&' . urlencode('language') . '=' . $language; // 파라미터설명

        curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $response = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($response, true);

        return $res;
    }


    /**
     * send app push
     * @param  String $heading
     * @param  String $content
     * @param  Object $data
     * @param  Array $ids
     */
    function createPush($title, $contents, $data, $ids, $date)
    {
        $heading = array(
            "en" => strip_tags(str_replace('<br>', ' ', $title)),
            "kr" => strip_tags(str_replace('<br>', ' ', $title))
        );
        $content = array(
            "en" => strip_tags($contents),
            "kr" => strip_tags($contents)
        );

        $data = array_merge((array) $data, ['deeplink'=> 'https://loda.travel/loda']);

        $fields = array(
            'app_id' => config('services.onesignal.app_id'),
            'include_external_user_ids' => $ids,
            'send_after' => $date . ' GMT+0900',
            'delayed_option' => 'timezone',
            'data' => $data,
            'contents' => $content,
            'headings' => $heading,
            'content_available' => true,
            'mutable_content' => true
        );

        $fields = json_encode($fields);
        
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

        Log::notice('ONESIGNAL push : ' . $response);
        // success : {"id":"e9996741-cc00-4b2f-9a4d-61b3fe25d0db","recipients":1,"external_id":null}
        // fail : {"errors":["Schedule Notifications may not be scheduled in the past."]}
        return $response;
    }


    /**
     * send app push
     * @param  String $heading
     * @param  String $content
     * @param  Object $data
     * @param  Array $ids
     */
    function createPublicPush($title, $contents, $data, $key, $date)
    {
        if ($key == 'goods') {
            $contents = '(광고) ' . $contents;
        }
        $heading = array(
            "en" => strip_tags(str_replace('<br>', ' ', $title)),
            "kr" => strip_tags(str_replace('<br>', ' ', $title))
        );
        $content = array(
            "en" => strip_tags($contents),
            "kr" => strip_tags($contents)
        );

        $data = array_merge((array) $data, ['deeplink' => 'https://loda.travel/loda']);

        $fields = array(
            'app_id' => config('services.onesignal.app_id'),
            'send_after' => $date . ' GMT+0900',
            'delayed_option' => 'timezone',
            'data' => $data,
            'contents' => $content,
            'headings' => $heading,
            'content_available' => true,
            'mutable_content' => true
        );

        if ($key == 'goods') {
            $fields['filters'] = [array(
                'field' => 'tag',
                'key' => 'marketing',
                'relation' => '=',
                'value' => 1
            )];
        } elseif ($key == 'any') {
            $fields['included_segments'] = array(
                'All'
            );
        };

        $fields = json_encode($fields);

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

        Log::notice('ONESIGNAL push field: ' . $fields);
        Log::notice('ONESIGNAL push : ' . $response);
        // success : {"id":"e9996741-cc00-4b2f-9a4d-61b3fe25d0db","recipients":1,"external_id":null}
        // fail : {"errors":["Schedule Notifications may not be scheduled in the past."]}
        return $response;
    }

    /**
     * cancel app push
     *
     * @param  String $push_id
     */
    function cancelPush($push_id)
    {
        $fields = array(
            'app_id' => config('services.onesignal.app_id')
        );

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications/" . $push_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . config('services.onesignal.rest_api_key')
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        Log::notice('ONESIGNAL push cancel : ' . $push_id);

        // {"success":true}
        return $response;
    }


    /**
     * get weather data from opeweather
     *
     * @param Double $lat, $lng
     * @param Json
     */

    function getWeatherFromOpenweather($lat, $lon, $cnt = 16)
    {
        $ch = curl_init();

        $url = env('OPENWEATHER_API'); // URL
        $queryParams = '?' . urlencode('appid') . '=' . env('OPENWEATHER_API_KEY'); // Service Key
        $queryParams .= '&' . urlencode('lat') . '=' . $lat; // 파라미터설명
        $queryParams .= '&' . urlencode('lon') . '=' . $lon; // 파라미터설명
        $queryParams .= '&' . urlencode('units') . '=metric'; // 파라미터설명
        $queryParams .= '&' . urlencode('cnt') . '=' . $cnt; // 파라미터설명

        // echo $url . $queryParams;
        curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $response = curl_exec($ch);
        curl_close($ch);

        Log::notice('weather api : ' . $url . $queryParams);
        Log::notice('weather : ' . $response);
        $res = json_decode($response, true);

        return $res;
    }


    /**
     * get weather data from opeweather
     *
     * @param $number
     * @param $currency
     */

    function getWeatherByCityAndDate($today, $city, $start, $end)
    {
        $res = DB::table('cities_weather')
                ->where('city_id', $city)
                ->where('created_at', 'like', $today.'%')
                ->where('date', '>=', $start)
                ->where('date', '<=', $end)
                ->groupBy('date')
                ->orderBy('date')
                ->get(['id', 'city_id', 'date', 'icon', 'temp_max', 'temp_min', 'mark', 'created_at']);

        return $res;
    }


    /**
     * check place_id group
     *
     * @param $place_id
     */
    function getTargetPlaceId($place_id)
    {
        $target = $place_id;
        $res = DB::table('cities_group')->where('original', $place_id)->first(['target']);
        if ($res) {
            $target = $res->target;
        }
        return $target;
    }



    /**
     * check card key
     *
     * @param $key
     * @param $exist
     * @param $card
     */
    function getCardItem($key, $sub_key, $card)
    {
        return (string) property_exists($card, $sub_key) ? $card->$sub_key : $card->$key;
    }



    function pushAfter($res_push, $loda)
    {
        $res_push_arr = json_decode($res_push, true);
        if (\array_key_exists('id', $res_push_arr)) {
            DB::table('loda')->where('id', $loda->id)->update(['onesignal_id' => $res_push_arr['id']]);
            Log::info('* onesignal push success');
        } else {
            Log::info('onesignal push error: ' . $res_push);
        };
    }


    function getFlagAtTravel($travel_id) 
    {
        $flags = [];
        $attractions = DB::table('travel_attraction')->where('travel_id', $travel_id)->get();
        foreach ($attractions as $value) {
            $city = DB::table('cities')->where('id', $value->city_id)->first();
            $country = DB::table('countries')->where('id', $city->country_id)->first();
            array_push($flags, $country->flag);
        }
        // 국기는 중복없이 3개까지만
        $res = array_slice(array_unique($flags), 0, 3);

        return $res;
    }

    /**
     * onesignal tags
     *
     * @param  String $player_id
     * @param  String $key
     * @param  String $value
     */
    function setOnesignalTags($player_id, $key, $value)
    {
        $fields = array(
            'tags' => array(
                $key => $value
            )
        );

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, env('ONESIGNAL_API'). '/players/'. $player_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . config('services.onesignal.rest_api_key')
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        Log::info('onesignal tags: ' . $response);

        return $response;
    }


    /**
     * signal api
     *
     * @param  String $player_id
     * @param  String $key
     * @param  String $value
     */
    function getSignaParse($message)
    {
        $fields = array(
            'message' => $message
        );

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, env('SIGNAL_DEV_API') . '/6009/saveSmsMessage.do');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'vs: PT_010_201908.01.1'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        Log::info('onesignal tags: ' . $response);
        $response = json_decode($response, true);
        curl_close($ch);

        

        return $response;
    }


    /**
     * exchangerate api
     *
     * @param  String $code
     */
    function exchangerate($code)
    {
        $url = env('EXCHANGERATE_API') . $code;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

        Log::info('EXCHANGERATE_API: ' . $url);
        return $response;
    }

    /**
     * get traveling loda data
     *
     * @param  Object $travel
     * @param  Datetime $date
     */
    function getIngInfo($travel, $date)
    {
        $title = '';
        $contents = '';
        $day_diff = '';
        $date = \substr($date, 0, 10);
        $dStart = new DateTime($travel['start']);
        $dEnd  = new DateTime($date);
        $day_diff = $dStart->diff($dEnd)->format('%a');
        $next_day = date('Y-m-d', strtotime($date . " +1 days"));
        
        if ($day_diff == 0) {
            $title = config('services.ing_title_first');
            $contents = config('services.ing_contents_first');
        } elseif ($date == $travel['end']) {
            $title = config('services.ing_title_last');
            $contents = config('services.ing_contents_last');
        } else {
            $order = ($day_diff + 1).'일째날';
            if ($day_diff < 30) $order = config('services.days')[$day_diff];
            $title = '<b>여행 ' . $order . '</b>은<br>어떠셨어요?';
            $contents = config('services.ing_contents');
        }

        $res = [
            'title' => $title,
            'contents' => $contents
        ];
        $weather = getWeatherOfTheDay($next_day, $travel);
        
        $res = array_merge($res, $weather);
        return $res;
    }


    /**
     * get weather of city and date
     *
     * @param  Object $travel
     * @param  Datetime $date
     */
    function getWeatherOfTheDay($date, $travel, $weather_comments = 1)
    {
        $weather = [];
        $one_w = '';
        $cities = getCitiesOfTheDay($date, $travel);
        foreach ($cities as $v) {
            $city_weather = DB::table('cities_weather')
                ->where('city_id', $v->id)
                ->where('date', $date)
                ->orderBy('id', 'DESC')
                ->first();
            if (!$city_weather && $date >= date('Y-m-d')) {
                $getWeather = getWeatherFromOpenweather($v->lat, $v->lng, 2);
                if ($getWeather) {
                    if (\array_key_exists('list', $getWeather)) {
                        foreach ($getWeather['list'] as $value) {
                            $check = '';
                            try {
                                $check = DB::table('cities_weather')->where('date', date('Y-m-d', $value['dt']))->where('city_id', $v->id)->first();
                            } catch (\Throwable $th) { }
                            try {
                                if (!$check) {
                                    $icon = count($value['weather']) ? $value['weather'][0]['icon'] : '';
                                    $set = [
                                        'city_id' => $v->id,
                                        'date' => date('Y-m-d', $value['dt']),
                                        'temp_max' => $value['temp']['max'],
                                        'temp_min' => $value['temp']['min'],
                                        'icon' => $icon,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ];

                                    DB::table('cities_weather')->insert($set);
                                }
                            } catch (\Throwable $th) {
                                Log::notice('error insert city weather : ' . json_encode($value));
                            };
                        }
                        $city_weather = DB::table('cities_weather')
                            ->where('city_id', $v->id)
                            ->where('date', $date)
                            ->orderBy('id', 'DESC')
                            ->first();
                    }
                }
            }

            if ($city_weather) {
                $city_weather->city = $v;
                array_push($weather, $city_weather);
                $one_w = $city_weather;
            }
        }

        $res = [
            'weather' => $weather
        ];

        if ($weather_comments) {
            $comment = ['내일은'];
            $comment_second = [];

            try {
                $comment_second = config('services.weather_comments.' . \substr($one_w->icon, 0, 2));
                $comment = array_merge($comment, $comment_second);
            } catch (\Throwable $th) { }

            if ($one_w) {
                $msg = '';
                if ($one_w->temp_min < 0) {
                    $msg = config('services.weather_comments_temp')[0];
                } elseif ($one_w->temp_min < 5) {
                    $msg = config('services.weather_comments_temp')[1];
                } elseif ($one_w->temp_min < 10) {
                    $msg = config('services.weather_comments_temp')[2];
                } elseif ($one_w->temp_max > 33) {
                    $msg = config('services.weather_comments_temp')[6];
                } elseif ($one_w->temp_max > 27) {
                    $msg = config('services.weather_comments_temp')[5];
                } elseif ($one_w->temp_max > 20) {
                    $msg = config('services.weather_comments_temp')[4];
                } elseif ($one_w->temp_max > 12) {
                    $msg = config('services.weather_comments_temp')[3];
                } else {
                    $comment = [];
                }

                if ($msg) {
                    if (count($comment) > 2) $comment[1] = $comment[1] . ' ' . $msg;
                    else array_push($comment, $msg);
                }
            } else {
                $comment = [];
            }

            $res['weather_comments'] = $comment;
        }
        return $res;
    }

    /**
     * get cities of the days 
     *
     * @param  Object $travel
     * @param  Datetime $date
     */
    function getCitiesOfTheDay($date, $travel)
    {
        $res = [];
        $att = DB::table('travel_attraction')->orderBy('start_at')->where('travel_id', $travel['id'])->get();
        foreach ($att as $v) {
            if ($v->start_at <= $date && $v->end_at >= $date) {
                $city = DB::table('cities')
                    ->where('id', $v->city_id)
                    ->first(['name', 'name_en', 'lat', 'lng', 'id']);
                if ($city) array_push($res, $city);
            }
        }
        return $res;
    }

    /**
     * 여행 화폐 목록
     *
     * @param  Int $travel_id
     * @return Array \App\Models\Currency
     */
    function travelCurrencyList($travel_id)
    {
        $mCurrency = new CurrencyRepository;
        $mTravelCurrency = new TravelAttractionRepository;
        $mCountry = new CountryRepository;

        $currencies = $mCurrency->getDefault();
        
        $cities = $mTravelCurrency->getByTravel($travel_id);
        $cities->each(function ($val) use ($currencies, $mCountry) {
            // 도시에 화폐가 있는 경우
            if ($val->city->currency) {
                // 수집되는 화폐일 경우
                if ($val->city->currency->collected) $currencies->push($val->city->currency);
            } else {
                $country_currencies = $mCountry->getCurrency($val->city->country_id);
                if ($country_currencies->count()) {
                    // 도시의 언어가 없는 경우, 국가의 언어를 가져옴
                    $country_currencies->each(function ($value) use ($currencies) {
                        // 수집되는 화폐일 경우
                        if ($value->collected) $currencies->push($value);
                    });
                }
            }
        });

        $res = $currencies->unique('code');

        return $res;
    }

    /**
     * 여행 short url from Naver
     *
     * @param  Int $travel_id
     * @return String url
     */
    function shortUrl($data)
    {
        // 네이버 단축URL Open API 예제
        $client_id = env('NAVER_CLIENT_ID'); // 네이버 개발자센터에서 발급받은 CLIENT ID
        $client_secret = env('NAVER_SECRET'); // 네이버 개발자센터에서 발급받은 CLIENT SECRET
        $encText = urlencode($data);
        $postvars = "?url=" . $encText;
        $url = env('NAVER_SHORTURL_API') . $postvars;

        $is_post = false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, $is_post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch,CURLOPT_POSTFIELDS, $postvars);
        $headers = array();
        $headers[] = "X-Naver-Client-Id: " . $client_id;
        $headers[] = "X-Naver-Client-Secret: " . $client_secret;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //echo "status_code:" . $status_code . "<br>";
        curl_close($ch);
        
        if ($status_code == 200) {
            $res = json_decode($response);
            return $res->result->url;
        }
    }
