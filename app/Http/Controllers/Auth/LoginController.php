<?php
namespace App\Http\Controllers\Auth;

use App\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use Validator;
use \Laravel\Passport\Http\Controllers\AccessTokenController as ATC;
use App\Traits\PassportToken;

class LoginController extends ATC
{
  use PassportToken;
  
  public function issueToken(ServerRequestInterface $request)
  {
    try {
      $validator = Validator::make($request->getParsedBody(), [
        'username' => 'required|email',
        'password' => 'required|max:32',
        'device_id' => 'required|max:255',
        'device_type' => 'required|max:32',
      ]);
      if ($validator->fails()) {
        return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
      }
      //get username (default is :email)
      $req = $request->getParsedBody();
  
      //get user
      $user = User::where('email', '=', $req['username'])->first();
      if ($user) {
        // user_device
        $device = DB::table('users_device')->where('user_id', $user->id)->where('device_id', $req['device_id'])->first();
        
        if (!$device) {
          DB::table('users_device')->insert(
            [
              'user_id' => $user->id,
              'device_id' => $req['device_id'],
              'device_type' => $req['device_type'],
              'last_login_at' => now(),
            ]
          );
        } else {
          DB::table('users_device')->where('id', $device->id)->update(
            [
              'last_login_at' => now(),
            ]
          );
        }
      } else {
        return $this->sendError(config('message.exception.NO_USER'), '', 500);
      }

      $tokenResponse = parent::issueToken($request);
      

      //convert response to json string
      $content = $tokenResponse->getContent();

      //convert json to array
      $data = json_decode($content, true);

      if (isset($data["error"])) {
        return $this->sendError($data['error'], $data['message'], 401);
      }
      if (isset($data["access_token"])) {
        return $this->sendResponse($data, '');
      }
      
    } catch (ModelNotFoundException $e) { // email notfound
      return $this->sendError(config('message.exception.NO_USER'), '', 500);
    } catch (OAuthServerException $e) { //password not correct..token not granted
      return $this->sendError(config('message.exception.IVD_CRD'), '', 500);
    } catch (Exception $e) {
      return $this->sendError(config('message.exception.SRV_ERR'), '', 500);
    }
  }

  public function issueTokenBySns(ServerRequestInterface $request)
  {
    // try {
      $validator = Validator::make($request->getParsedBody(), [
        'sns_type' => 'required',
        'key' => 'required',
        'device_id' => 'required|max:255',
        'device_type' => 'required|max:32',
      ]);
      if ($validator->fails()) {
        return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
      }
      //get username (default is :email)
      $req = $request->getParsedBody();

      //get user
      // $user = User::where('key', '=', $req['username'])->first();
      $sns = DB::table('users_join')->where( 'sns_type', '=', $req['sns_type'])->where('key', $req['key'])->first();
      if (!$sns) {
        return $this->sendError(config('message.exception.NO_USER'), '', 500);
      }
      $user = User::where('id', '=', $sns->user_id)->first();
      if ($user) {
        // user_device
        $device = DB::table('users_device')->where('user_id', $user->id)->where('device_id', $req['device_id'])->first();

        if (!$device) {
          DB::table('users_device')->insert(
            [
              'user_id' => $user->id,
              'device_id' => $req['device_id'],
              'device_type' => $req['device_type'],
              'last_login_at' => now(),
            ]
          );
        } else {
          DB::table('users_device')->where('id', $device->id)->update(
            [
              'last_login_at' => now(),
            ]
          );
        }

        // $tokenResponse = parent::issueToken($request);
        $data = $this->getBearerTokenByUser($user, 1, false);

        if (isset($data["errorCode"])) {
          return $this->sendError($data['errorCode'], $data['message'], 401);
        }
        if (isset($data["access_token"])) {
          return $this->sendResponse($data, '');
        }
      } else {
        return $this->sendError(config('message.exception.NO_USER'), '', 500);
      }

      
    // } catch (ModelNotFoundException $e) { // email notfound
    //   return $this->sendError(config('message.exception.NO_USER'), '', 500);
    // } catch (OAuthServerException $e) { //password not correct..token not granted
    //   return $this->sendError(config('message.exception.IVD_CRD'), '', 500);
    // } catch (Exception $e) {
    //   return $this->sendError(config('message.exception.SRV_ERR'), '', 500);
    // }
  }

  /**
   * success response method.
   *
   * @return \Illuminate\Http\Response
   */
  public function sendResponse($result, $message)
  {
    /*
    $response = [
      'success' => true,
      'data' => $result,
      'message' => $message,
    ];
    */
    $response = $result;

    return response()->json($response, 200);
  }


  /**
   * return error response.
   *
   * @return \Illuminate\Http\Response
   */
  public function sendError($error, $errorMessages = [], $code = 404)
  {
    if (gettype($errorMessages) == 'object') {
      $values = array('username', 'password', 'device_id', 'device_type', 'name', 'sns_type', 'key');
      foreach ($values as $val) {
        if (array_key_exists($val, $errorMessages->messages())) {
          $errorMessages = $errorMessages->messages()[$val][0];
          break;
        }
      }
    }

    $response = [
      'errorCode' => $error,
    ];

    $response['message'] = '';
    if (!empty($errorMessages)) {
      $response['message'] = $errorMessages;
    }


    return response()->json($response, $code);
  }

}