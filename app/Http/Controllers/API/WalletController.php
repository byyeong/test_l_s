<?php

namespace App\Http\Controllers\API;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\BaseController;
use App\Repositories\API\WalletRepository;
use App\Repositories\API\TravelAttractionRepository;
use App\Repositories\API\CountryRepository;
use App\Repositories\API\CurrencyRepository;
use App\Repositories\API\ToolFileRepository;

class WalletController extends BaseController
{
    /**
     * @var WalletRepository
     * @var TravelAttractionRepository
     * @var CountryRepository
     * @var CurrencyRepository
     * @var ToolFileRepository
     */
    protected $walletRepository;
    protected $travelAttractionRepository;
    protected $countryRepository;
    protected $currencyRepository;
    protected $toolFileRepository;


    /**
     * @param WalletRepository $walletRepository
     * @param TravelAttractionRepository $travelAttractionRepository
     * @param countryRepository $countryRepository
     * @param CurrencyRepository $currencyRepository
     * @param ToolFileRepository $toolFileRepository
     */
    public function __construct(WalletRepository $walletRepository, TravelAttractionRepository $travelAttractionRepository
            , CountryRepository $countryRepository, CurrencyRepository $currencyRepository
            , ToolFileRepository $toolFileRepository) 
    {
        $this->walletRepository = $walletRepository;
        $this->travelAttractionRepository = $travelAttractionRepository;
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
        $this->toolFileRepository = $toolFileRepository;
    }


    /**
     * 지금 환율 저장 API
     *
     * @param  Int $travel_id
     */
    private function exchangeApi($travel_id)
    {
        $currencies = travelCurrencyList($travel_id);
        try {
            foreach ($currencies as $c) {
                $value = $c;
                $exc = exchangerate($value->code);
                if (\array_key_exists('rates', $exc)) {
                    $this->currencyRepository->saveTravelCurrency($travel_id, $value->id, $exc['rates']['KRW']);
                }
            }
        } catch (\Throwable $th) {
            return false;
        }
        
        return true;
    }




    /**
     * 화폐 목록
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Int $travel_id
     * @return Array \App\Models\Currency
     */
    private function currencyListMake($req, $travel_id)
    {
        $currencies = travelCurrencyList($travel_id);
        if (\array_key_exists('with', $req)) {
            if (strpos($req['with'], 'exc') > -1) {
                foreach ($currencies as $c) {
                    $val = $c;
                    $val->exc = $this->currencyRepository->getExchange($travel_id, $val->id);
                }
            }
            if (strpos($req['with'], 'flag') > -1) {
                foreach ($currencies as $cc) {
                    $val = $cc;
                    $country = $this->countryRepository->getById($val->rel_country_id);
                    $val->flag = env('AWS_CLOUDFRONT') . $country->flag;
                }
            }
        }

        return $currencies;
    }

    /**
     * 화폐 목록
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Int $travel_id
     * @return Array \App\Models\Currency
     */
    public function currencyList(Request $request, $travel_id)
    {
        $req = $request->all();
        $currencies = $this->currencyListMake($req, $travel_id);
        
        return $this->sendResponse($currencies->filter()->values());
    }



    /**
     * 예산
     *
     * @param  Int $travel_id
     * @return \App\Models\Budget
     */
    public function getBudget($travel_id)
    {   
        $res = array( 'budget' => null );
        $budget = $this->walletRepository->getBudget($travel_id);
        if ($budget) {
            $budget->currency = $this->currencyRepository->getById($budget->currency_id);
            $budget->currency->exc = $this->currencyRepository->getExchange($travel_id, $budget->currency_id);
            $res['budget'] = $budget;
        }
        return $this->sendResponse($res);
    }



    /**
     * 예산 저장
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Int $travel_id
     * @return \App\Models\Budget
     */
    public function storeBudget(Request $request, $travel_id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'currency_id' => 'required|exists:currencies,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        $req = $request->all();
        $ex = $this->walletRepository->getBudget($travel_id);
        if ($ex) {
            $this->walletRepository->saveBudget($travel_id, $req['amount'], $req['currency_id'], $ex->id);
        } else {
            $this->walletRepository->saveBudget($travel_id, $req['amount'], $req['currency_id']);
            // 환율 저장
            $this->exchangeApi($travel_id);
        }

        $budget = $this->walletRepository->getBudget($travel_id);
        $budget->currency = $this->currencyRepository->getById($budget->currency_id);
        $budget->currency->exc = $this->currencyRepository->getExchange($travel_id, $budget->currency_id);
        $res['budget'] = $budget;

        return $this->sendResponse($res);
    }



    /**
     * 환율 새로 저장
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Int $travel_id
     * @return Array \App\Models\Currency
     */
    public function reNewCurrency(Request $request, $travel_id)
    {
        $this->exchangeApi($travel_id);
        $request= array(
            'with' => 'flag, exc'
        );
        $currencies = $this->currencyListMake($request, $travel_id);

        return $this->sendResponse($currencies->filter()->values());
    }



    /**
     * 시그널에서 문자 파싱
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Int $travel_id
     * @return Json
     */
    public function parse(Request $request, $travel_id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $req = $request->all();
        $res = getSignaParse(\str_replace('\n', '', $req['message']));

        if (\array_key_exists('resultcode', $res)) {
            if ($res['resultcode'] == '00') {
                return $this->sendResponse($res);
            }
        }
        
        return $this->sendError(config('message.exception.IVD_ARG'), 'failed to parse');
    }




    /**
     * 지갑 내역 저장
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Int $travel_id
     * @param  Int $wallet_id
     * @return \App\Models\Wallet
     */
    public function store(Request $request, $travel_id, $wallet_id = 0)
    {
        $ifRequired = $wallet_id? '': 'required|';
        $used_type_ids = count(config('services.used_type')) - 1;
        $validator = Validator::make($request->all(), [
            'price' => $ifRequired.'numeric',
            'currency_id' => $ifRequired.'exists:currencies,id',
            'payment' => 'starts_with:card,cash',
            'title' => $ifRequired,
            'used_type'=> $ifRequired.'integer|max:'.$used_type_ids,
            'date' => $ifRequired.'date_format:Y-m-d H:i:s',
            'text' => 'nullable|string',
            'gmt' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        
        $res = '';
        $req = $request->all();
        $req['travel_id'] = $travel_id;
        if ($wallet_id) {
            $res = $this->walletRepository->updateById($wallet_id, $req);
        } else {
            $res = $this->walletRepository->create($req);
        }

        $res = $this->walletRepository->with('files', 'currency')->getById($res->id);

        return $this->sendResponse($res);
    }

    /**
     * 사진 저장
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Int $travel_id
     * @param  Int $wallet_id
     * @return \App\Models\Wallet
     */
    public function storeFile(Request $request, $travel_id, $wallet_id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|max:'.config('services.file_limit.size'),
            'file_name' => 'string'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $res = $this->walletRepository->getById($wallet_id);
        if ($res->travel_id != $travel_id) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no permission for add file');
        }

        $req = $request->all();
        $req['travel_id'] = $travel_id;

        $user = $request->user();
        $file = $req['file'];
        $exp = explode('.', $file->getClientOriginalName());
        $name = time() . '.' . $exp[count($exp) - 1];
        $filePath = 'user/travel/' . $user->id . '/wallet/' . $name;
        Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
        $filePath = '/' . $filePath;

        $file_name = $file->getClientOriginalName();
        if (array_key_exists('file_name', $req)) $file_name = $req['file_name'];

        $file_req = [
            'file' => $filePath,
            'file_name' => $file_name,
            'tool_type' => config('services.tool_model.wallet'),
            'tool_id' => $wallet_id,
            'ord' => 1
        ];
        
        if ($res->files->count() > 0) {
            // 기존 이미지 삭제 :: 첨부파일이 1개만 유지
            $res_file = $res->files[0];
            if ($res_file->file) {
                Storage::disk('s3')->delete(substr($res_file->file, 1));
            }
            $this->toolFileRepository->updateById($res_file->id, $file_req);
        } else {
            $this->toolFileRepository->create($file_req);
        }
        
        $res = $this->walletRepository->with('files', 'currency')->getById($wallet_id);

        return $this->sendResponse($res, '');
    }

    /**
     * 사진 삭제
     *
     * @param  Int $travel_id
     * @param  Int $wallet_id
     */
    public function deleteFile($travel_id, $wallet_id)
    {
        $res = $this->walletRepository->getById($wallet_id);

        $res->files->each(function ($item) {
            if ($item->file) {
                Storage::disk('s3')->delete(substr($item->file, 1));
            }
            $item->delete();
        });
        return 1;
    }

    /**
     * list
     *
     * @param  Int  $travel_id
     * @return Array \App\Models\Wallet
     */
    public function index($travel_id)
    {
        // 본인 노트 확인
        $res = $this->walletRepository->where('travel_id', $travel_id)->orderBy('date')->with('files', 'currency')->get();

        return $this->sendResponse($res, '');
    }




    /**
     * get one
     *
     * @param  int  $travel_id
     * @param  Int  $wallet_id
     * @return \App\Models\Wallet
     */
    public function get($travel_id, $wallet_id)
    {
        // 본인 노트 확인
        $res = $this->walletRepository->with('files', 'currency')->getById($wallet_id);
        $res->currency->exc = $this->currencyRepository->getExchange($travel_id, $res->currency_id);
        return $this->sendResponse($res, '');
    }




    /**
     * Remove 
     *
     * @param  Int  $travel_id
     * @param  Int  $wallet_id
     */
    public function delete($travel_id, $wallet_id)
    {
        $this->deleteFile($travel_id, $wallet_id);
        $this->walletRepository->where('travel_id', $travel_id)->where('id', $wallet_id)->delete();
        exit;
    }




    /**
     * Remove All
     *
     * @param  Int  $travel_id
     */
    public function deleteAll($travel_id)
    {
        $all = $this->walletRepository->where('travel_id', $travel_id)->get();
        $all->each(function ($val) use ($travel_id) {
            $this->deleteFile($travel_id, $val->id);
        });
        $this->walletRepository->where('travel_id', $travel_id)->delete();
        exit;
    }
}
