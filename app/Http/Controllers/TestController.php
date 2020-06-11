<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Image;

class TestController 
{
    public function index() 
    {
    $pre = DB::table('loda')
      ->where('date', 'like', date('Y-m-d') . '%')
      ->where('onesignal_id', '')
      ->where('deleted_at', null)
      ->get();

      foreach ($pre as $k => $p) {
        if ($k == 1) break;
        else echo $k;
      }
      
      print_r(3234323);
      exit(1);
        $response = $this->sendMessage();
        $return["allresponses"] = $response;
        $return = json_encode($return);

        $data = json_decode($response, true);
        print_r($data);
        $id = $data['id'];
        print_r($id);

        print("\n\nJSON received:\n");
        print($return);
        print("\n");
    }

    private function sendMessage()
    {
        $content      = array(
          "en" => 'English Message'
        );
        $hashes_array = array();
        array_push($hashes_array, array(
          "id" => "like-button",
          "text" => "from loda server",
          "icon" => "http://i.imgur.com/N8SN8ZS.png",
          "url" => "https://yoursite.com"
        ));
        $fields = array(
          'app_id' => "bd413879-8375-4ff3-bfc2-b9e542171f80",
          'included_segments' => array(
            'last session 3hours ago'
          ),
          'data' => array(
            "foo" => "bar"
          ),
          'contents' => $content,
          'web_buttons' => $hashes_array
        );

        $fields = json_encode($fields);
        print("\nJSON sent:\n");
        print($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json; charset=utf-8',
          'Authorization: Basic OTczN2JmMjYtZDdkMy00MTNlLThkMWItOTlkMmFmZDUxZTdi'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
    public function test()
    {
      $json = Storage::disk('local')->get('weather_city.json');
      $json = json_decode($json, true);
      //print_r($json[0]);

      foreach ($json as $value) {
        $set =  [
          'ow_city_id' => $value['id'],
          'name' => $value['name'],
          'country' => $value[ 'country'],
          'lon' => $value[ 'coord']['lon'],
          'lat' => $value[ 'coord']['lat'],
        ];
        DB::table('cities_openweather')->insert($set);
      }




      exit(1);

      //$cities = DB::table('cities')->where('id', '>', 3650)->orderBy('id')->get();
      $cities = DB::table('cities')->whereNotNull('place_id')->where('id', '>', 4236)->orderBy('id')->get();
      foreach ($cities as $value) {
        
        $ch = curl_init();
        $url = 'https://maps.googleapis.com/maps/api/place/details/json'. '?' . urlencode('placeid') . '=' . urlencode($value->place_id). '&' . 'language=ko&fields=address_component'; // URL

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $response = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($response, true);

        // print_r($res['result']);
        // exit(1);

        $result = $res['result']['address_components'];
        foreach ($result as $val) {
            if (in_array('locality', $val['types'])) {
              DB::table('cities')->where('id', $value->id)->update(
                ['name' => $val['long_name']]
              );
              break;
            };
        };
      }

      exit(1);
        $cn = DB::table('countries')->get();

        //$ex = explode('/', );
        // $filename = 'zwe.svg';
        // $tempImage = tempnam(sys_get_temp_dir(), $filename);
        // copy('https://restcountries.eu/data/zwe.svg', $tempImage);
        // return response()->download($tempImage, $filename);

        // $url = "https://restcountries.eu/data/zwe.svg";
        // $contents = file_get_contents($url);
        // $name = substr($url, strrpos($url, '/') + 1);
        // Storage::put($name, $contents);

        //return 
        // exit(1);
        foreach ($cn as $key => $value) {
            $url = $value->flag;
            $contents = file_get_contents($url);
            $name = substr($url, strrpos($url, '/') + 1);
            Storage::put($name, $contents);

            
        }
        exit(1);





      // 국가정보 가져오기
      exit(1);
      $variable = DB::table('countries')->where('id', '>', 312)->get();

      foreach ($variable as $key => $value) {
        $cun = $value->alpha2Code;
        $ch = curl_init();
        $url = 'http://dataservice.accuweather.com/locations/v1/adminareas/' . $cun . '?apikey=MCsT9IUOJ1TM98rMo2BoaCuPwmQr5o0B&language=ko-kr'; // URL
        $queryParams = '?' . urlencode('ServiceKey') . '=ZStYu82MhtuXIBLzapaOF8lGfHubomYv2c2Tf3lg%2BcH5GoBmIqdlwmgNIR28Db9Y4fHO07ecCW0usLW5dtX32Q%3D%3D'; // Service Key
        $queryParams .= '&' . urlencode('arsId') . '=' . urlencode('25724'); // 파라미터설명

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $response = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($response, true);

        $country = DB::table('countries')->where('alpha2Code', $cun)->first();
              //print_r($res);
              //exit(1);
        foreach ($res as $city) {
          if ($city['LocalizedName']) {
            $ccc = DB::table('cities')->where('name', $city['LocalizedName'])->where('country', $country->id)->first();
            if ($city && !$ccc) {
              DB::table('cities')->insert([
                'name' => $city['LocalizedName'] ? $city['LocalizedName'] : '',
                'name_en' => $city['EnglishName'] ? $city['EnglishName'] : '',
                'level' => $city['Level'] ? $city['Level'] : '',
                'localized_type' => $city['LocalizedType'] ? $city['LocalizedType'] : '',
                'localized_type_en' => $city['EnglishType'] ? $city['EnglishType'] : '',
                'country' => $country->id,
              ]);
            }
          }


        }
      }


      print_r($res);

      exit(1);    
          /*
          $ch = curl_init();
          $url = 'https://restcountries.eu/rest/v2/all'; // URL
          $queryParams = '?' . urlencode('ServiceKey') . '=ZStYu82MhtuXIBLzapaOF8lGfHubomYv2c2Tf3lg%2BcH5GoBmIqdlwmgNIR28Db9Y4fHO07ecCW0usLW5dtX32Q%3D%3D'; // Service Key
          $queryParams .= '&' . urlencode('arsId') . '=' . urlencode('25724'); // 파라미터설명

          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HEADER, false);
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
          $response = curl_exec($ch);
          curl_close($ch);

          $res = json_decode($response, true);
      */
      foreach ($res as $key => $value) {
              /*
              DB::table('countries')->insert([
                  'name'=>$value['name'],
                  'topLevelDomain'=> implode(",", $value['topLevelDomain']),
                  'alpha2Code' => $value['alpha2Code'],
                  'alpha3Code' => $value['alpha3Code'],
                  'callingCodes' => sizeof($value['callingCodes'])? $value['callingCodes'][0]: '',
                  'capital' => $value['capital'],
                  'altSpellings' => sizeof($value['altSpellings']) ? $value['altSpellings'][0] : '',
                  'region' => $value['region'],
                  'subregion' => $value['subregion'],
                  'population' => $value['population'],
                  'lat' => sizeof($value['latlng'])? $value['latlng'][0]: '',
                  'lng' => sizeof($value['latlng']) ? $value['latlng'][1] : '',
                  'demonym' => $value['demonym'],
                  'gini' => $value['gini'],
                  'borders' => implode(",", $value['borders']),
                  //'nativeName' => $value['nativeName'],
                  'numericCode' => $value['numericCode'],
                  'flag' => $value['flag'],
                  'cioc' => $value['cioc']
              ]);
        */
        $con = DB::table('countries')->where('name', $value['name'])->first();

        if (sizeof($value['timezones'])) {
          foreach ($value['timezones'] as $val) {
            $cur = DB::table('timezones')->where('code', $val)->first();
            if (!$cur) {
              DB::table('timezones')->insert([
                'code' => $val
              ]);

              $cur = DB::table('timezones')->where('code', $val)->first();
            }
            DB::table('countries_timezone')->insert([
              'country_id' => $con->id,
              'timezone_id' => $cur->id
            ]);
          }
        }

              /*
              if (sizeof($value['regionalBlocs'])) {
                  foreach ($value['regionalBlocs'] as $val) {
                      $cur = DB::table('regional_blocs')->where('acronym', $val['acronym'])->first();
                      if (!$cur) {
                          DB::table('regional_blocs')->insert([
                              'acronym' => $val['acronym'],
                              'name' => $val['name'],
                          ]);

                          $cur = DB::table('regional_blocs')->where('acronym', $val['acronym'])->first();
                      }
                      DB::table('countries_regional_bloc')->insert([
                          'country_id' => $con->id,
                          'region_id' => $cur->id
                      ]);
                  }
              }
              
              if (sizeof($value['translations'])) {
                  foreach ($value['translations'] as $val) {
                      $cur = DB::table('translations')->where('de', $val['de'])->first();
                      if (!$cur) {
                          DB::table('translations')->insert([
                              'de' => $val['de'],
                              'es' => $val['es'],
                              'fr' => $val['fr'],
                              'ja' => $val['ja'],
                              'it' => $val['it'],
                              'br' => $val['br'],
                              'pt' => $val['pt']
                          ]);

                          $cur = DB::table('translations')->where('de', $val['de'])->first();
                      }
                      DB::table('countries_translation')->insert([
                          'country_id' => $con->id,
                          'translation_id' => $cur->id
                      ]);
                  }
              }

              if (sizeof($value['currencies'])) {
                  foreach ($value['currencies'] as $val) {
                      $cur = DB::table('currencies')->where('code', $val['code'])->first();
                      if ( !$cur) {
                          DB::table('currencies')->insert([
                              'code' => $val['code'],
                              'name' => $val['name'],
                              'symbol' => $val['symbol'],
                          ]);

                          $cur = DB::table('currencies')->where('code', $val['code'])->first();
                      }
                      DB::table('countries_currency')->insert([
                          'country_id' => $con->id,
                          'currency_id' => $cur->id
                      ]);
                  }
              }
        */
      }
      exit(1);
      $xml = simplexml_load_string($response);
      $json = json_encode($xml);
      $array = json_decode($json, true);


      if (array_key_exists('msgBody', $array)) {
        $request['lat'] = $array['msgBody']['itemList']['gpsX'];
        $request['lon'] = $array['msgBody']['itemList']['gpsY'];
      }

    }
}
