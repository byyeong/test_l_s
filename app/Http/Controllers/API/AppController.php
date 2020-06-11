<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Repositories\API\AppRepository;
use Illuminate\Support\Facades\DB;

class AppController extends BaseController
{
    /**
     * @var AppRepository
     */
    protected $appRepository;


    /**
     * @param AppRepository $appRepository
     */
    public function __construct(AppRepository $appRepository)
    {
        $this->appRepository = $appRepository;
    }

    /**
     * App config
     *
     * @return \App\Models\App
     */
    public function config()
    {
        $res = $this->appRepository->first();
        
        return $this->sendResponse($res, '');
    }


    /**
     * Last Notice number
     *
     * @return \App\Models\App
     */
    public function lastNoticeNo()
    {
        $res = DB::table('board')->where('type', 'notice')->orderBy('id', 'DESC')->first();

        return $this->sendResponse($res->id, '');
    }
}