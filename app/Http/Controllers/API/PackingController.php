<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Repositories\API\TravelPackingsCategoriesRepository;
use App\Repositories\API\PackingsRepository;
use App\Repositories\API\TravelPackingsRepository;
use App\Repositories\API\CategoriesRepository;
use App\Repositories\API\TravelRepository;
use Illuminate\Support\Facades\DB;
use Validator;

class PackingController extends BaseController
{
    /**
     * @var PackingsRepository
     * @var TravelPackingsCategoriesRepository
     * @var TravelPackingsRepository
     * @var CategoriesRepository
     * @var TravelRepository
     */
    protected $packingsRepository;
    protected $travelPackingsCategoriesRepository;
    protected $travelPackingsRepository;
    protected $categoriesRepository;
    protected $travelRepository;

    /**
     * @param PackingsRepository $packingsRepository
     * @param TravelPackingsCategoriesRepository $travelPackingsCategoriesRepository
     * @param TravelPackingsRepository $travelPackingsRepository
     * @param CategoriesRepository $categoriesRepository
     * @param travelRepository $travelRepository
     */
    public function __construct(PackingsRepository $packingsRepository,
            TravelPackingsCategoriesRepository $travelPackingsCategoriesRepository, 
            TravelPackingsRepository $travelPackingsRepository,
            CategoriesRepository $categoriesRepository,
            TravelRepository $travelRepository)
    {
        $this->packingsRepository = $packingsRepository;
        $this->travelPackingsCategoriesRepository = $travelPackingsCategoriesRepository;
        $this->travelPackingsRepository =$travelPackingsRepository;
        $this->categoriesRepository = $categoriesRepository;
        $this->travelRepository = $travelRepository;
    }

    


    /**
     * Previous travel list
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function pre(Request $request, $travel_id)
    {
        $user = $request->user();

        $travel = $this->travelRepository->getByUser($user->id);

        $travel->transform(function ($item) use ($travel_id) {
            if ($item->id != $travel_id) {
                return $item;
            }
        });
        
        $res = $travel->each(function ($item) {
            if ($item) {
                $packings = $this->travelPackingsCategoriesRepository->getOnlyPackingByTravel($item->id, config('services.tool_show.show'));
                // if ($item['kids']) $item['kids'] = array_map('intval', explode(',', $item['kids']));
                $item['packing_cnt'] = $packings->count();
                $item['packing_category_cnt'] = $packings->unique('categories_id')->count();
            }
        });

        return $this->sendResponse( $res->filter()->values(), '');
    }




    /**
     * Previous travel list
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createByCategory(Request $request, $travel_id) 
    {
        $validator = Validator::make($request->all(), [
            'categories' => 'required|regex:/^[0-9,]+$/'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $res = DB::transaction(function () use ($request, $travel_id) {
            $category_ids = explode(',', $request->only('categories')['categories']);

            // 사용자 분류 중에 체크해제 상태 인 분류가져오기
            $preCheck = $this->travelPackingsCategoriesRepository
                    ->getPackingByTravelInCategory($travel_id, $category_ids);
            $preCheckIds = $preCheck->unique('categories_id');

            $preCheckIdsArray = array();
            
            foreach ($preCheckIds as $value) {
                array_push($preCheckIdsArray, $value->categories_id);
            }

            $preCheck->each(function ($item) use ($travel_id) {
                // 사용자 분류 중 체크해제 상태였다 다시 체크상태가 되면 하위 패킹 아이템들도 보일 수 있도록 수정(show = 1)
                $this->travelPackingsCategoriesRepository->hiddenByCategory($travel_id, $item->categories_id, config('services.tool_show.show'));
            });

            // TODO hidden 데이터 아이디를 생성 데이터 아이디에서 빼야함.
            $category_ids = array_diff($category_ids, $preCheckIdsArray);

            $categories = $this->categoriesRepository->whereIn('id', $category_ids)->where('parent', config('services.tool_type.packings'))->get();
            $categories->each(function ($item) use ($travel_id) {
                $set = [
                    'categories_id' => $item->id,
                    'travel_id' => $travel_id
                ];
                $this->travelPackingsCategoriesRepository->create($set);
            });
            $packings = $this->travelPackingsCategoriesRepository->getPackingInByCategory($travel_id, $category_ids);

            $packings->each(function ($item) use ($travel_id) {
                $set = [
                    'packings_id' => $item->packings_id,
                    'categories_id' => $item->categories_id,
                    'travel_id' => $travel_id,
                    'checked' => config('services.tool_checked.unchecked')
                ];
                $this->travelPackingsCategoriesRepository->create($set);
            });

            return true;
        });
        
        if (!$res) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'failed create packing item list');
        }
    }




    /**
     * Previous travel list
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteByCategory(Request $request, $travel_id) {
        $validator = Validator::make($request->all(), [
            'categories' => 'required|regex:/^[0-9,]+$/'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $category_ids = explode(',', $request->only('categories')['categories']);
        $categories = $this->categoriesRepository->whereIn('id', $category_ids)->get();
        $categories->each(function ($item) use ($travel_id) {
            if ($item->type == config('services.tool_personalization.custom')) {
                $this->travelPackingsCategoriesRepository->hiddenByCategory($travel_id, $item->id, config('services.tool_show.hidden'));
            } else {
                $this->travelPackingsCategoriesRepository->deleteMyByCategory($travel_id, [$item->id]);
            }
        });
    }




    /**
     * Previous travel list
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createByTravel(Request $request, $travel_id) 
    {
        $validator = Validator::make($request->all(), [
            'other_travel_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        $other_travel_id = $request->only('other_travel_id')['other_travel_id'];
        $res = DB::transaction(function () use ($travel_id, $other_travel_id) {
            // 이전 패킹 지우기
            $this->travelPackingsCategoriesRepository->deleteByTravel($travel_id);

            $packings = $this->travelPackingsCategoriesRepository->getPackingByTravel($other_travel_id);

            if ($packings->count() == 0) {
                return $this->sendError(config('message.exception.IVD_ARG'), 'no packing list at other travel');
            }

            $packings->each(function ($item) use ($travel_id) {
                $set = [
                    'packings_id' => $item->packings_id,
                    'categories_id' => $item->categories_id,
                    'travel_id' => $travel_id,
                    'show' =>$item->show,
                    'checked' => config('services.tool_checked.unchecked')
                ];
                $this->travelPackingsCategoriesRepository->create($set);
            });

            return true;
        });

        if (!$res) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'failed create packing item list');
        }
    }




    /**
     * Store a check resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function category($travel_id)
    {
        $categories = $this->categoriesRepository->getByInParent('type', [ config('services.tool_personalization.base'), config('services.tool_personalization.activity')], config( 'services.tool_type.packings'));
        $packings = $this->travelPackingsCategoriesRepository->getPackingByTravel($travel_id)->unique('categories_id');

        if ($packings->count() > 0) {
            $custom_category = $this->travelPackingsCategoriesRepository
                    ->getByTypeParent($travel_id, config('services.tool_personalization.custom'), config('services.tool_type.packings'));

            foreach ($custom_category->unique('categories_id') as $item) {
                $set = collect([
                    'id' => $item->categories_id,
                    'title' => $item->title,
                    'type' => $item->type,
                    'parent' => $item->parent
                ]);
                $categories->push($set);
            };
            
            $checked = $packings->map(function ($item) {
                if ($item->show == 1) {
                    return $item->categories_id;
                }
            });
            
            $checked_ids = $checked->values()->all();
            $categories->each(function ($item) use ( $checked_ids) {
                
                $item['checked'] = 0;
                if (in_array($item['id'], $checked_ids)) {
                    $item['checked'] = 1;
                }
            });
        } else {
            $travel = $this->travelRepository->getById($travel_id);
            // travel with baby
            $ex_baby = 0;
            if ($travel->kids) {
                $kids = explode(',', $travel->kids);
                foreach ($kids as $k) {
                    if ($k < 4) $ex_baby = 1;
                }
            }
            // travel with baby
            $categories->each(function ($item) use ($ex_baby) {
                $item['checked'] = $item->show;
                // travel with baby
                if ($ex_baby && $item->id == 12) {
                    $item['checked'] = 1;
                }
                // travel with baby
            });   
        }

        return $this->sendResponse( $categories, '');
    }




    /**
     * Store a check resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listCategory(Request $request, $travel_id, $category_id)
    {
        $res = $this->travelPackingsCategoriesRepository->getMyByCategoryABBR($travel_id, $category_id);
        $res->each(function ($item) {
            $item->packing;
            $item->category;
        });
        return $this->sendResponse($res, '');
    }




    /**
     * Store a check resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createCategory(Request $request, $travel_id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $title = trim($request->only('title')['title']);
        $category = $this->categoriesRepository
                ->getByTitle($title, config('services.tool_personalization.custom')
                , config('services.tool_type.packings'));
        $data = [
            'categories_id' => $category->id,
            'travel_id' => $travel_id,
            'checked' => config('services.tool_checked.checked')
        ];
        $res = $this->travelPackingsCategoriesRepository->store($data);

        return $this->sendResponse($category, '');
    }




    /**
     * Store a check resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateCategory(Request $request, $travel_id, $category_id) 
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        // type : custom만 수정 가능
        $old_cat = $this->categoriesRepository->getById($category_id);
        if ($old_cat->type != config('services.tool_personalization.custom')) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'uneditable catogory');
        }

        // 패킹 아이템을 가지고 있는지 판별
        $cat_cnt = $this->travelPackingsCategoriesRepository->getPackingInByCategory($travel_id, [$category_id]);
        if ($cat_cnt->count() == 0) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no packing in that category');
        }

        $res = DB::transaction(function () use ($request, $travel_id, $category_id) {
            $title = $request->only('title')['title'];
            $new_category = $this->categoriesRepository
                ->getByTitle($title,
                config('services.tool_personalization.custom'),
                config('services.tool_type.packings'));

            $this->travelPackingsCategoriesRepository->updateByCategory($new_category->id, $category_id, $travel_id);

            return $new_category;
        });

        if ( !$res) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'failed category update');
        }
        return $this->sendResponse($res, '');
    }




    /**
     * Store a check resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteCategory(Request $request, $travel_id, $category_id)
    {
        // type : custom만 수정 가능
        $old_cat = $this->categoriesRepository->getById($category_id);
        if ($old_cat->type != config('services.tool_personalization.custom')) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'uneditable catogory');
        }

        $res = $this->travelPackingsCategoriesRepository->deleteMyByCategory($travel_id, [$category_id]);

        if (!$res) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'failed category delete');
        }
    }




    /**
     * packing list resource.
     *
     * @param  int  $travel_id
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $travel_id)
    {
        $packings = $this->travelPackingsCategoriesRepository->getPackingByTravel($travel_id, config('services.tool_show.show'));
        $packings->each(function ($item) {
            $item->packing;
            $item->category;
        });

        return $this->sendResponse($packings, '');
    }




    /**
     * Display the specified resource.
     *
     * @param  int  $travel_id
     * @return \Illuminate\Http\Response
     */
    public function checkReset(Request $request, $travel_id) 
    {
        $this->travelPackingsCategoriesRepository->resetCheck($travel_id);
        exit(1);
    }




    /**
     * Store a check resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function check(Request $request, $travel_id, $packing_id, $check)
    {
        $req['check'] = $check;
        $validator = Validator::make($req, [
            'check' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        $packing = $this->travelPackingsCategoriesRepository->where('travel_id', $travel_id)
                ->where('id', $packing_id)->first();
        
        if ($packing) {
            $data = ['checked' => $check];
            $res = $this->travelPackingsCategoriesRepository->updateById($packing_id, $data);

            return $this->sendResponse($res, '');
        } else {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no packing item to check');
        }
    }




    public function qty(Request $request, $travel_id, $packing_id, $qty)
    {
        $req['qty'] = $qty;
        $validator = Validator::make($req, [
            'qty' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }
        $packing = $this->travelPackingsCategoriesRepository->where('travel_id', $travel_id)
            ->where('id', $packing_id)->first();

        if ($packing) {
            $data = ['qty' => $qty];
            $res = $this->travelPackingsCategoriesRepository->updateById($packing_id, $data);

            return $this->sendResponse($res, '');
        } else {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no packing item to update qty');
        }
    }

    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $travel_id) 
    {
        $validator = Validator::make($request->all(), [
            '*.title' => 'required|string',
            '*.category_id' => 'required|integer',
            '*.qty' => 'required|integer',
            '*.check' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $req = $request->all();

        
        foreach ($req as $key => $value) {
            $category = $this->categoriesRepository->getById($value['category_id']);
            if ($category->parent != config('services.tool_type.packings')) {
                return $this->sendError(config('message.exception.IVD_ARG'), $key . '. category can not use');
            }
        }

        $res = DB::transaction(function () use ($req, $travel_id) {

            $res = array();
            foreach ($req as $value) {
                $packing = $this->packingsRepository->getByTitle(trim($value['title']));
                $pre_check = $this->travelPackingsCategoriesRepository
                    ->getByCategoryPackingTravel($value['category_id'], $packing->id, $travel_id);
                if ( !$pre_check) {
                    $set = [
                        'packings_id' => $packing->id,
                        'categories_id' => $value['category_id'],
                        'travel_id' => $travel_id,
                        'checked' => $value['check'],
                        'qty' => $value['qty'],
                    ];
                    $result = $this->travelPackingsCategoriesRepository->create($set);
                    $result->packing;
                    $result->category;
                    array_push($res, $result);
                }
            }
            return $res;
        });

        if ( !$res) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'failed packing create');
        }

        return $this->sendResponse($res, '');
    }




    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $travel_id, $packing_id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'qty' => 'integer',
            'check' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError(config('message.exception.IVD_ARG'), $validator->errors());
        }

        $req = $request->all();
        $packing_category = $this->travelPackingsCategoriesRepository->getById($packing_id);

        $category = $this->categoriesRepository->getById($packing_category->categories_id);
        if ($category
            ->type != config('services.tool_personalization.custom')
            || $category->parent != config('services.tool_type.packings')) {
     
               return $this->sendError(config('message.exception.IVD_ARG'), 'can not use category');
        }

        $packing = $this->packingsRepository->getByTitle($req['title']);
        $pre_checke = $this->travelPackingsCategoriesRepository
            ->getByCategoryPackingTravel($category->id, $packing->id, $travel_id);
        if ($pre_checke) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'duplicated packing');
        }

        $set = [
            'packings_id' => $packing->id,
        ];
        if (array_key_exists('qty', $req)) $set['qty'] = $req['qty'];
        if (array_key_exists('check', $req)) $set['checked'] = $req['check'];
        $res = $this->travelPackingsCategoriesRepository->updateById($packing_category->id, $set);

        return $this->sendResponse($res, '');
    }

    


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $travel_id, $packing_id
     * @return \Illuminate\Http\Response
     */
    public function delete($travel_id, $packing_id)
    {
        $packing_category = $this->travelPackingsCategoriesRepository->getById($packing_id);
        if ($packing_category->travel_id != $travel_id) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'no permission for delete packing');
        }

        $category = $this->categoriesRepository->getById($packing_category->categories_id);
        if ($category->parent != config('services.tool_type.packings')) {
            return $this->sendError(config('message.exception.IVD_ARG'), 'can not delete category\'s packing');
        }

        $this->travelPackingsCategoriesRepository->deleteById($packing_id);
        exit(1);
    }
}
