<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('register', 'Auth\RegisterController@register');
Route::post('sns/exist', 'Auth\RegisterController@snsExist');
Route::post('register/sns', 'Auth\RegisterController@registerSns');
Route::post('login/sns', 'Auth\LoginController@issueTokenBySns');
Route::post('oauth/token', 'Auth\LoginController@issueToken');
Route::get('check_email', 'Auth\RegisterController@checkEmail');
Route::get('login/help', 'Auth\ResetPasswordController@loginHelp');
Route::get('app/config', 'API\AppController@config');
Route::get('app/lastnotice', 'API\AppController@lastNoticeNo');
Route::get('push_test', 'API\TodoController@pushTest');
Route::get('test', 'TestController@index');
Route::post('signoff/sns', 'Auth\SignOffController@withSns');
Route::get('timeline', 'API\TimelineController@index');

Route::group([
    'middleware' => 'auth:api'
], function () {
    Route::get('loda', 'API\LodaController@index');
    Route::delete('loda/{id}', 'API\LodaController@delete');
});

Route::group([
    'namespace' => 'Auth',
    'prefix' => 'password'
], function () {
    Route::post('create', 'PasswordResetController@create');
    Route::get('find/{token}', 'PasswordResetController@find');
    Route::post('reset', 'PasswordResetController@reset');
});

Route::group([
    'namespace' => 'User',
    'prefix' => 'user',
    'middleware' => 'auth:api'
], function () {
    Route::get('/', 'ProfileController@index');
    Route::get('token', 'ProfileController@makeToken');
    Route::patch('travel/type', 'ProfileController@travelType');
    Route::patch('additional', 'ProfileController@additional');
    Route::get('email/verify', 'ProfileController@emailVerify');
    Route::patch('name', 'ProfileController@name');
    Route::patch('password', 'ProfileController@password');
    Route::post('picture', 'ProfileController@picture');
    Route::get('accept', 'ProfileController@acceptList');
    Route::patch('accept', 'ProfileController@accept');
    Route::get('signoff', 'ProfileController@signoff');
    Route::get('signoff/with', 'ProfileController@signoffWithSns');
    Route::get('notification', 'ProfileController@notification');
    Route::patch('/', 'ProfileController@update');
    Route::get('tour/count/{type}', 'ProfileController@tourCnt');
});

Route::group([
    'namespace' => 'API',
    'prefix' => 'travel',
    'middleware' => 'auth:api'
], function () {
    Route::get('/image', 'TravelController@firstImage');
    Route::get('/country', 'TravelController@countryInfo');
    Route::get('/{travel_id}/url', 'TravelController@getUrl');
    Route::get('/{travel_id}', 'TravelController@index');
    Route::get('/', 'TravelController@list');
    Route::post('/', 'TravelController@create');
    Route::patch('/{travel_id}', 'TravelController@update');
    Route::delete('/{travel_id}', 'TravelController@delete');
    Route::post('/{travel_id}/picture', 'TravelController@picture');
});

Route::group([
    'namespace' => 'API',
    'prefix' => 'travel',
    'middleware' => ['auth:api', 'mytravel']
], function () {
    Route::get('/{travel_id}/tool', 'ToolController@index');
    Route::get('/{travel_id}/todo', 'TodoController@index');
    Route::patch('/{travel_id}/todo/{todo_id}/check/{check}', 'TodoController@check');
    Route::get('/{travel_id}/todo/reset', 'TodoController@checkReset');
    Route::post('/{travel_id}/todo', 'TodoController@store');
    Route::patch('/{travel_id}/todo/{todo_id}', 'TodoController@update');
    Route::delete('/{travel_id}/todo/{todo_id}', 'TodoController@delete');

    Route::get('/{travel_id}/packing', 'PackingController@index');
    Route::patch('/{travel_id}/packing/{packing_id}/check/{check}', 'PackingController@check');
    Route::patch('/{travel_id}/packing/{packing_id}/qty/{qty}', 'PackingController@qty');

    Route::get('/{travel_id}/packing/pre', 'PackingController@pre');
    Route::post('/{travel_id}/packing/bycategory', 'PackingController@createByCategory');
    Route::delete('/{travel_id}/packing/bycategory', 'PackingController@deleteByCategory');
    Route::post('/{travel_id}/packing/bytravel', 'PackingController@createByTravel');
    Route::get('/{travel_id}/packing/category', 'PackingController@category');
    Route::delete('/{travel_id}/packing/{packing_id}', 'PackingController@delete');
    Route::post('/{travel_id}/packing', 'PackingController@create');
    Route::post('/{travel_id}/packing/category', 'PackingController@createCategory');
    Route::get('/{travel_id}/packing/category/{category_id}', 'PackingController@listCategory');
    Route::patch('/{travel_id}/packing/category/{category_id}', 'PackingController@updateCategory');
    Route::delete('/{travel_id}/packing/category/{category_id}', 'PackingController@deleteCategory');
    Route::patch('/{travel_id}/packing/reset', 'PackingController@checkReset');
    Route::patch('/{travel_id}/packing/{packing_id}/check/{check}', 'PackingController@check');
    Route::patch('/{travel_id}/packing/{packing_id}/qty/{qty}', 'PackingController@qty');
    Route::patch('/{travel_id}/packing/{packing_id}', 'PackingController@update');

    Route::post('/{travel_id}/note', 'NoteController@store');
    Route::post('/{travel_id}/note/{note_id}/file', 'NoteController@storeFile');
    Route::delete( '/{travel_id}/note/{note_id}/file', 'NoteController@deleteFile');
    Route::delete('/{travel_id}/note/{note_id}', 'NoteController@delete');
    Route::patch('/{travel_id}/note/{note_id}', 'NoteController@store');
    Route::get('/{travel_id}/note/{note_id}', 'NoteController@show');


    Route::post('/{travel_id}/diary', 'DiaryController@store');
    Route::get('/{travel_id}/diary', 'DiaryController@index');
    Route::post('/{travel_id}/diary/{diary_id}/file', 'DiaryController@storeFile');
    Route::delete('/{travel_id}/diary/{diary_id}/file', 'DiaryController@deleteFile');
    Route::delete('/{travel_id}/diary/{diary_id}', 'DiaryController@delete');
    Route::patch('/{travel_id}/diary/{diary_id}', 'DiaryController@store');
    Route::get('/{travel_id}/diary/{diary_id}', 'DiaryController@get');

    Route::get('/{travel_id}/wallet/currency_list', 'WalletController@currencyList');
    Route::post('/{travel_id}/wallet/budget', 'WalletController@storeBudget');
    Route::get('/{travel_id}/wallet/budget', 'WalletController@getBudget');
    Route::post('/{travel_id}/wallet/parse', 'WalletController@parse');
    Route::post('/{travel_id}/wallet', 'WalletController@store');
    Route::get('/{travel_id}/wallet', 'WalletController@index');
    Route::delete('/{travel_id}/wallet', 'WalletController@deleteAll');
    Route::get('/{travel_id}/wallet/newcurrency', 'WalletController@reNewCurrency');
    Route::get('/{travel_id}/wallet/{wallet_id}', 'WalletController@get');
    Route::delete('/{travel_id}/wallet/{wallet_id}', 'WalletController@delete');
    Route::patch('/{travel_id}/wallet/{wallet_id}', 'WalletController@store');
    Route::post('/{travel_id}/wallet/{wallet_id}/file', 'WalletController@storeFile');
    Route::delete('/{travel_id}/wallet/{wallet_id}/file', 'WalletController@deleteFile');
    
});