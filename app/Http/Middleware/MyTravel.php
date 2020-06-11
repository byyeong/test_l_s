<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\GeneralException;
use App\Repositories\API\TravelRepository;

class MyTravel
{
    /**
* @var TravelRepository
*/
    protected $travelRepository;


    /**
* @param TravelRepository $travelRepository
*/
    public function __construct(TravelRepository $travelRepository)
    {
        $this->travelRepository = $travelRepository;
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
        $travel = $this->travelRepository->getById( $request->route('travel_id'));
        if ($request->user()->id != $travel->user_id) {
            throw new GeneralException(config('message.exception.NO_PEM_TRV_EDIT','It\' s not your travel.'));
        }

        return $next($request);
    }
}

