<?php

namespace App\Http\Controllers\API;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\BaseController;
use App\Repositories\API\NotesRepository;
use App\Repositories\API\ToolFileRepository;

class NoteController extends BaseController
{
    /**
     * @var NotesRepository
     * @var ToolFileRepository
     */
    protected $notesRepository;
    protected $toolFileRepository;


    /**
     * @param NotesRepository $notesRepository
     * @param ToolFileRepository $toolFileRepository
     */
    public function __construct(NotesRepository $notesRepository, ToolFileRepository $toolFileRepository) 
    {
        $this->notesRepository = $notesRepository;
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

        $validator = Validator::make($request->all(), [
            'title' => 'string',
            'contents' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        $req = $request->all();
        $req['travel_id'] = $travel_id;

        if ($id) {
            $res = $this->notesRepository->updateById($id, $req);
        } else {
            $res = $this->notesRepository->create($req);
        }
        $res->files;

        return $this->sendResponse($res, '');
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeFile(Request $request, $travel_id, $note_id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|max:'.config('services.file_limit.size'),
            'file_name' => 'string'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        // 본인 노트 확인
        $res = $this->notesRepository->getById($note_id);
        if ($res->travel_id != $travel_id) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no permission for add file');
        }

        $req = $request->all();
        $req['travel_id'] = $travel_id;

        $user = $request->user();
        $file = $req['file'];
        $exp = explode('.', $file->getClientOriginalName());
        $name = time() . '.' . $exp[count($exp) - 1];
        $filePath = 'user/travel/' . $user->id . '/note/' . $name;
        Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
        $filePath = '/' . $filePath;

        $file_name = $file->getClientOriginalName();
        if (array_key_exists('file_name', $req)) $file_name = $req['file_name'];

        $file_req = [
            'file' => $filePath,
            'file_name' => $file_name,
            'tool_type' => config('services.tool_model.note'),
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
        
        $res = $this->notesRepository->getById($res->id);
        $res->files;

        return $this->sendResponse($res, '');
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($travel_id, $note_id)
    {
        // 본인 노트 확인
        $res = $this->notesRepository->getById($note_id);
        if ($res->travel_id != $travel_id) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no permission for show note');
        }

        $res->files;
        return $this->sendResponse($res, '');
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($travel_id, $note_id)
    {
        // 본인 노트 확인
        $res = $this->notesRepository->getById($note_id);
        if ($res->travel_id != $travel_id) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no permission for delete note');
        }

        if ($res->files->count() > 0) {
            $res->files->each(function ($item) {
                if ($item->file) {
                    Storage::disk('s3')->delete(substr($item->file, 1));
                }
                $item->delete();
            });
        }
        $res->delete();
    }



    /**
     * Delete a note attached file resource in storage.
     *
     * @param  $travel_id, $note_id
     * @return 
     */
    public function deleteFile($travel_id, $note_id)
    {
        // 본인 노트 확인
        $res = $this->notesRepository->getById($note_id);
        if ($res->travel_id != $travel_id) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no permission for delete file');
        }

        $res->files->each(function ($item) {
            if ($item->file) {
                Storage::disk('s3')->delete(substr($item->file, 1));
            }
            $item->delete();
        });
        exit(1);
    }
}
