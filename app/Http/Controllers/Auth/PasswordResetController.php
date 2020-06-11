<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\User;
use App\PasswordReset;
use Validator;

class PasswordResetController extends BaseController
{
  /**
   * Create token password reset
   *
   * @param  [string] email
   * @return [string] message
   */
  public function create(Request $request)
  {
        $validator = Validator::make($request->only('email'), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        $user = User::where('email', $request->email)->first();
        if (!$user)
            return $this->sendError(config('message.exception.NO_USER'));

        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => str_random(60)
            ]
        );
        if ($user && $passwordReset)
            $user->notify(
                new PasswordResetRequest($passwordReset->token)
            );

        $success['email'] = $user->email;
        return $this->sendResponse($success, 'Send Email Success.');
    }
    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find($token)
    {
        $passwordReset = PasswordReset::where('token', $token)
            ->first();
        $res = 1;

        if (!$passwordReset) {
            $res = 401;
        }
        else {
            if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
                $passwordReset->delete();
                $res = 402;
            } 
        }

        return view('passwordReset')
            ->withRes($res)
            ->withData($passwordReset);
    }
     /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|confirmed',
            'token' => 'required|string'
        ]);
        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->email]
        ])->first();
        if (!$passwordReset)
            return response()->json([
                'message' => 'This password reset token is invalid.'
            ], 404);
        $user = User::where('email', $passwordReset->email)->first();
        if (!$user)
            return response()->json([
                'message' => 'We can\'t find a user with that e-mail address.'
    ], 404);
    $user->password = bcrypt($request->password);
    $user->save();
    $passwordReset->delete();
    $user->notify(new PasswordResetSuccess($passwordReset));
    return response()->json($user);
  }
}