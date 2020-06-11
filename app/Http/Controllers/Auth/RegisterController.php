<?php


namespace App\Http\Controllers\Auth;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\BaseController as BaseController;
use App\Repositories\API\LodaRepository;
use Validator;
use App\User;

class RegisterController extends BaseController
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
   * @param LodaRepository $lodaRepository
   */
  public function __construct(LodaRepository $lodaRepository) {
    $this->lodaRepository = $lodaRepository;
  }


  /**
   * Register api
   *
   * @return \Illuminate\Http\Response
   */
  public function register(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required',
      'email' => 'required|email|unique:users',
      'password' => 'required|min:8|max:32',
      'password_confirmation' => 'required|same:password',
      'acceptance_sms' => 'boolean',
      'acceptance_push' => 'boolean',
      'acceptance_email' => 'boolean',
      'image' => 'image|max:'. config('services.file_limit.size'),
    ]);

    if ($validator->fails()) {
      return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
    }

    $input = $request->all();
    $input['password'] = bcrypt($input['password']);

    $res = DB::transaction(function () use ($input) {
      $input['acceptance_sms'] = $input['acceptance_sms'] ? date('Y-m-d H:i:s') : null;
      $input['acceptance_push'] = $input['acceptance_push'] ? date('Y-m-d H:i:s') : null;
      $input['acceptance_email'] = $input['acceptance_email'] ? date('Y-m-d H:i:s') : null;

      $user = User::create($input);
      if ($user) {
        $input['user_id'] = $user->id;

        $filePath = '';
        if (\array_key_exists('image', $input)) {
          $file = $input['image'];
          $name = time() . $file->getClientOriginalName();
          $filePath = 'user/profile/' . $name;
          Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
        }

        // user acceptance
        DB::table('users')->where('id', $user->id)
            ->update([
              'acceptance_sms' => $input['acceptance_sms'],
              'acceptance_push' => $input['acceptance_push'],
              'acceptance_email' => $input['acceptance_email'],
              'last_login_at' => now(),
              'image' => '/'.$filePath
            ]);

        // welcome loda card
        $this->lodaRepository->createBySet($user->id, 0, 0, config('services.loda_card_id.welcome'), date('Y-m-d H:i:s'));
        // 기간 이벤트 카드 
        $this->lodaRepository->sendTermsEventCard(User::find($user->id));

        return $user;

      }

      return $this->sendError(config('message.exception.USER_C_FAIL'));
    });

    if ($res) {
      $success['token'] = $res->createToken('MyApp')->accessToken;
      $success['name'] = $res->name;

      return $this->sendResponse($success, 'User register successfully.');
    }
  }


  /**
   * Register api
   *
   * @return \Illuminate\Http\Response
   */
  public function registerSns(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required',
      'sns_type' => 'required',
      'key' => 'required|unique:users_join',
      'secret' => '',
      'acceptance_sms' => 'boolean',
      'acceptance_push' => 'boolean',
      'acceptance_email' => 'boolean',
      'image' => 'image|max:'. config('services.file_limit.size'),
    ]);

    if ($validator->fails()) {
      return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
    }

    $input = $request->all();

    $res = DB::transaction(function () use ($input) {
      $input['acceptance_sms'] = $input['acceptance_sms'] ? date('Y-m-d H:i:s') : null;
      $input['acceptance_push'] = $input['acceptance_push'] ? date('Y-m-d H:i:s') : null;
      $input['acceptance_email'] = $input['acceptance_email'] ? date('Y-m-d H:i:s') : null;

      $user = User::create($input);
      if ($user) {
        $input['user_id'] = $user->id;

        $filePath = '';
        if (\array_key_exists('image', $input)) {
          $file = $input['image'];
          $name = time() . $file->getClientOriginalName();
          $filePath = 'user/profile/' . $name;
          Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
        }

        // user join
        DB::table('users_join')->insert([
            'user_id' => $user->id,
            'sns_type' => $input['sns_type'],
            'key' => $input['key'],
            'secret' => array_key_exists('secret', $input)? $input['secret']: ''
        ]);

        // user acceptance
        DB::table('users')->where('id', $user->id)
          ->update([
            'acceptance_sms' => $input['acceptance_sms'],
            'acceptance_push' => $input['acceptance_push'],
            'acceptance_email' => $input['acceptance_email'],
            'last_login_at' => now(),
            'image' => '/' . $filePath
          ]);

        // welcome loda card
        $this->lodaRepository->createBySet($user->id, 0, 0, config('services.loda_card_id.welcome'), date('Y-m-d H:i:s', strtotime('-3 minutes')));
        // 기간 이벤트 카드 
        $this->lodaRepository->sendTermsEventCard(User::find($user->id));

        return $user;
      }

      return $this->sendError(config('message.exception.USER_C_FAIL'));
    });

    if ($res) {
      $success['token'] = $res->createToken('MyApp')->accessToken;
      $success['name'] = $res->name;

      return $this->sendResponse($success, 'User register successfully.');
    }
  }

  public function snsExist(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'key' => 'required|unique:users_join',
      'sns_type' => 'required',
    ]);

    if ($validator->fails()) {
      return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
    }

    $success['key'] = $request->only('key')['key'];

    return $this->sendResponse($success, '');
  }


  /**
   * Handle an authentication attempt.
   *
   * @param  \Illuminate\Http\Request $request
   *
   * @return Response
   */
  public function authenticate(Request $request)
  {
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
      // Authentication passed...
      print_r('success');
    } else {
      print_r('fail');
    }
  }

  /**
   * email unique check api
   *
   * @return \Illuminate\Http\Response
   */
  public function checkEmail()
  {
    $get = array('email' => Input::get('email'));
    $validator = Validator::make($get, [
      'email' => 'required|email|unique:users',
    ]);

    if ($validator->fails()) {
      return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
    }

    $success['email'] = Input::get('email');

    return $this->sendResponse($success, '');
  }

}