<?php

namespace App\Repositories\API;

use App\Models\Loda;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

/**
 * Class LodaRepository.
 */
class LodaRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Loda::class;
    }

    public function getByUserUntilDate($user, $date, $perpage = 10)
    {
        return $this->model
                ->where( 'date', '<=', $date)
                ->where('user_id', $user)
                ->where('date', '!=', '0000-00-00 00:00:00')
                ->orderBy('date', 'DESC')
                ->orderBy('id', 'DESC')
                ->with('card')
                ->paginate($perpage);
    }

    public function getByUserFromDateInType($user, $date, $cards, $perpage = 10)
    {
        return $this->model
            ->whereIn('card_id', $cards)
            ->where('date', '>=', $date)
            ->where('user_id', $user)
            ->where('date', '!=', '0000-00-00 00:00:00')
            ->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')
            ->with('card')
            ->paginate($perpage);
    }

    public function check($travel_id, $card_id, $endpoint_id) 
    {
        return $this->model
                ->where('travel_id', $travel_id)
                ->where('endpoint_id', $endpoint_id)
                ->where('card_id', $card_id)
                ->first();
    }

    public function createBySet($user_id, $travel_id, $endpoint_id, $card_id, $date)
    {
        $set = [
            'user_id' => $user_id,
            'travel_id' => $travel_id,
            'endpoint_id' => $endpoint_id,
            'card_id' => $card_id,
            'date' => $date,
        ]; 
        return $this->model->create($set);
    }

    public function deleteRow($loda)
    {
        if ( $loda->onesignal_id) {
            cancelPush($loda->onesignal_id);
        }
        return $this->deleteById($loda->id);
    }

    public function sendTermsEventCard($user)
    {
        $terms_cards = DB::table('cards')
            ->whereIn('type', ['any', 'goods'])
            ->where('send_start', '<=', now())
            ->where('send_end', '>=', now())
            ->whereNull('deleted_at')
            ->orderBy('send_date')
            ->get();

        foreach ($terms_cards as $key => $card) {
            if ($card->type == 'goods' && !$user->acceptance_push) break;
            $min = $key + 5;
            $times = date('Y-m-d H:i:s', strtotime('+'.$min.' minutes'));
            $this->createWithPush($card, $user->id, null, null, $times);
        }
    }


    public function createWithPush($card, $user_id, $travel_id, $endpoint_id, $date)
    {
        return $this->model
            ->create([
                'user_id' => $user_id,
                'travel_id' => $travel_id,
                'endpoint_id' => $endpoint_id,
                'card_id' => $card->id,
                'date' => $date
            ]);
        // $push = createPush(getCardItem('title', 'push_title', $card), getCardItem('contents', 'push_contents', $card), $card, [$user_id], $card->send_date);
        // pushAfter($push, $loda);
    }
}
