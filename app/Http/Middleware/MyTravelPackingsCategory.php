<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\GeneralException;
use App\Repositories\API\TravelPackingsCategoriesRepository;

class MyTravelPackingsCategory
{
    /**
     * @var TravelPackingsCategoriesRepository
     */
    protected $travelPackingsCategoriesRepository;


    /**
     * @param TravelPackingsCategoriesRepository $travelPackingsCategoriesRepository
     */
    public function __construct(TravelPackingsCategoriesRepository $travelPackingsCategoriesRepository)
    {
        $this->travelPackingsCategoriesRepository = $travelPackingsCategoriesRepository;
    }

    /**
* Handle an incoming request.
*
* @param \Illuminate\Http\Request $request
* @param \Closure $next
* @param string|null $guard
* @return mixed
*/
    public function handle($request, Closure $next)
    {
        $travel = $this->travelPackingsCategoriesRepository->where('travel_id', $request->route('travel_id'))->where('packings_category_id', $request->route('category_id'));
        if ( !$travel) {
            throw new GeneralException(config('message.exception.NO_PEM_TRV_EDIT','It\' s not your packing category.'));
        }

        return $next($request);
    }
}

