<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;


class BaseController extends Controller
{
  /**
   * success response method.
   *
   * @return \Illuminate\Http\Response
   */
  public function sendResponse($result = '', $message = '', $code = 200)
  {
    /*
    $response = [
      'success' => true,
      'data' => $result,
      'message' => $message,
    ];
     */
    $response = $result;

    return response()->json($response, $code);
  }


  /**
   * return error response.
   *
   * @return \Illuminate\Http\Response
   */
  public function sendError($error, $errorMessages = [], $code = 404)
  {
    if (gettype($errorMessages) == 'object') {
      $values = array_keys($errorMessages->messages());
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