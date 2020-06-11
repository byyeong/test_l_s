<?php

namespace App\Http\Controllers\API;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\BaseController;
use App\Repositories\API\DiaryRepository;
use App\Repositories\API\ToolFileRepository;

class DiaryController extends BaseController
{
    /**
     * @var DiaryRepository
     * @var ToolFileRepository
     */
    protected $diaryRepository;
    protected $toolFileRepository;


    /**
     * @param DiaryRepository $diaryRepository
     * @param ToolFileRepository $toolFileRepository
     */
    public function __construct(DiaryRepository $diaryRepository, ToolFileRepository $toolFileRepository) 
    {
        $this->diaryRepository = $diaryRepository;
        $this->toolFileRepository = $toolFileRepository;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $travel_id, $id = 0)
    {
        $ifRequired = $id? '': 'required|';
        $validator = Validator::make($request->all(), [
            'title' => $ifRequired.'string',
            'text' => 'nullable|string',
            'date' => $ifRequired.'date_format:Y-m-d H:i:s',
            'gmt' => 'nullable',
            'lat' => 'nullable',
            'lon' => 'nullable',
            'method' => 'nullable',
            'weather' => 'nullable',
            'temp_max' => 'integer|nullable',
            'temp_min' => 'integer|nullable',
            'address' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        $req = $request->all();
        if (array_key_exists('lat', $req)) $req['lat'] = Crypt::encryptString($req['lat']);
        if (array_key_exists('lon', $req)) $req['lon'] = Crypt::encryptString($req['lon']);
        $req['travel_id'] = $travel_id;

        if ($id) {
            $res = $this->diaryRepository->updateById($id, $req);
            $latlon = array();
            if (array_key_exists('lat', $req)) $latlon['lat'] = $req['lat'];
            if (array_key_exists('lon', $req)) $latlon['lon'] = $req['lon'];
            if (sizeof($latlon)) {
                $this->diaryRepository->updateById($id, $latlon);
            }
            $res->files;
        } else {
            $res = $this->diaryRepository->create($req);
        }
        if ($res) {
            if ($res->getOriginal('lat') && strpos($res->getOriginal('lat'), 'JpdiI6I')) {
                $res->lat = Crypt::decryptString($res->getOriginal('lat'));
            }
            if ($res->getOriginal('lon') && strpos($res->getOriginal('lon'), 'JpdiI6I')) {
                $res->lon = Crypt::decryptString($res->getOriginal('lon'));
            } 
        }

        $this->diaryRepository->logs($res->id, $request->user()->id, $req);

        return $this->sendResponse($res, '');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeFile(Request $request, $travel_id, $diary_id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|max:'.config('services.file_limit.size'),
            'file_name' => 'string'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        // 본인 노트 확인
        $res = $this->diaryRepository->getById($diary_id);
        if ($res->travel_id != $travel_id) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no permission for add file');
        }

        $req = $request->all();
        $req['travel_id'] = $travel_id;

        $user = $request->user();
        $file = $req['file'];
        $exp = explode('.', $file->getClientOriginalName());
        $name = time() . '.' . $exp[count($exp) - 1];
        $filePath = 'user/travel/' . $user->id . '/diary/' . $name;
        Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
        $filePath = '/' . $filePath;

        $file_name = $file->getClientOriginalName();
        if (array_key_exists('file_name', $req)) $file_name = $req['file_name'];

        $file_req = [
            'file' => $filePath,
            'file_name' => $file_name,
            'tool_type' => config('services.tool_model.diary'),
            'tool_id' => $res->id,
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
        
        $res = $this->diaryRepository->with('files')->getById($res->id);
        if ($res) {
            if ($res->getOriginal('lat') && strpos($res->getOriginal('lat'), 'JpdiI6I')) {
                $res->lat = Crypt::decryptString($res->getOriginal('lat'));
            }
            if ($res->getOriginal('lon') && strpos($res->getOriginal('lon'), 'JpdiI6I')) {
                $res->lon = Crypt::decryptString($res->getOriginal('lon'));
            }
        }

        return $this->sendResponse($res, '');
    }



    /**
     * list
     *
     * @param  int  $travel_id
     * @return \Illuminate\Http\Response
     */
    public function index($travel_id)
    {
        // 본인 노트 확인
        $res = $this->diaryRepository->where('travel_id', $travel_id)->orderBy('date')->with('files')->get();
        $res->each(function ($item) {
            if ($item->getOriginal('lat') && strpos($item->getOriginal('lat'), 'JpdiI6I')) {
                $item->lat = Crypt::decryptString($item->getOriginal('lat'));
            }
            if ($item->getOriginal('lon') && strpos($item->getOriginal('lon'), 'JpdiI6I')) {
                $item->lon = Crypt::decryptString($item->getOriginal('lon'));
            }
        });
        return $this->sendResponse($res, '');
    }




    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function get($travel_id, $note_id)
    {
        // 본인 노트 확인
        $res = $this->diaryRepository->with('files')->getById($note_id);

        if ($res->getOriginal('lat') && strpos($res->getOriginal('lat'), 'JpdiI6I')) {
            $res->lat = Crypt::decryptString($res->getOriginal('lat'));
        } 
        if ($res->getOriginal('lon') && strpos($res->getOriginal('lon'), 'JpdiI6I')) {
            $res->lon = Crypt::decryptString($res->getOriginal('lon'));
        } 

        return $this->sendResponse($res, '');
    }




    /**
     * Delete a note attached file resource in storage.
     *
     * @param  $travel_id, $diary_id
     * @return 
     */
    public function deleteFile($travel_id, $diary_id)
    {
        // 본인 노트 확인
        $res = $this->diaryRepository->getById($diary_id);

        $res->files->each(function ($item) {
            if ($item->file) {
                Storage::disk('s3')->delete(substr($item->file, 1));
            }
            $item->delete();
        });
        return 1;
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($travel_id, $diary_id)
    {
        $this->deleteFile($travel_id, $diary_id);
        $this->diaryRepository->where('travel_id', $travel_id)->where('id', $diary_id)->delete();
        exit(1);
    }
}
