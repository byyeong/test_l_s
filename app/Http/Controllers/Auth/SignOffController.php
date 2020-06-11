<?php
namespace App\Http\Controllers\Auth;

use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\User;

use App\Http\Controllers\BaseController as BaseController;

class SignOffController extends BaseController
{
    public function withSns(Request $request)
    { 
        $header_txt = $request->header('Authorization');
        $header_arr = explode(' ', $header_txt);

        $header = [
            'type' => $header_arr[0],
            'key' => $header_arr[1]
        ];

        $validator = Validator::make($request->all(), [
            'app_id' => '',
            'referrer_type' => '',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors(), 500);
        }
        $sns_type = config('services.sns_type.'.$header['type']);
        if (!$sns_type) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'unknown type sns', 500);
        }
        $user_sns = DB::table('users_join')->where('sns_type', $sns_type)->where('key', $request->only('user_id')['user_id'])->first();
        if (! $user_sns) {
            return $this->sendError(config('message.exception.NO_USER'), 'unknown sns key', 500);
        }
        $user = User::where('id', $user_sns->user_id)->first();
        if ($user) {
            
            DB::beginTransaction();
            try {
                // email 삭제
                $user->update([
                    'email' => null
                ]);
                // loda 삭제
                $loda = DB::table('loda')->where('user_id', $user->id)->get();
                foreach ($loda as $value) {
                    if ($value->onesignal_id && $value->date >= date('Y-m-d')) {
                        // push 예정 데이터 삭제
                        cancelPush($value->onesignal_id);
                    }
                }
                DB::table('loda')->where('user_id', $user->id)->delete();
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

        } else {
            return $this->sendError(config('message.exception.NO_USER'), '', 500);
        }
    }

}