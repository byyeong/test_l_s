<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;
use NotificationChannels\OneSignal\OneSignalPayloadFactory;

class OneSignalPushRequest extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($title, $content, $data, $ids, $times)
    {
        $this->title = $title;
        $this->content = $content;
        $this->data = $data;
        $this->ids = $ids;
        $this->times = $times;
    }

    public function via($notifiable)
    {
        return [OneSignalChannel::class];
    }

    // public function toOneSignal($notifiable)
    // {

    //     return OneSignalMessage::create()
    //         ->subject("Your {$notifiable->service} account was approved!")
    //         ->body("Click here to see details.")
    //         ->include_external_user_ids(4)
    //         ->url('http://onesignal.com');
    // }

    /**
     * send app push
     * @param  String $heading
     * @param  String $content
     * @param  Object $data
     * @param  Array $ids
     * @return \Illuminate\Http\Response
     */
    public function create($title, $contents, $data, $ids)
    {
        $heading = array(
            "en" => $title,
            "kr" => $title
        );
        $content = array(
            "en" => $contents,
            "kr" => $contents
        );

        // $ids = array();
        // array_push($ids, 4);

        $fields = array(
            'app_id' => config('services.onesignal.app_id'),
            'include_external_user_ids' => $ids,
            'send_after' => '2019-05-02 18:32:00 GMT+0900',
            'delayed_option' => 'timezone',
            'data' => $data,
            'contents' => $content,
            'headings' => $heading,
            'content_available' => true,
            'mutable_content' => true
        );

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . config('services.onesignal.api_key')
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        // success : {"id":"e9996741-cc00-4b2f-9a4d-61b3fe25d0db","recipients":1,"external_id":null}
        // fail : {"errors":["Schedule Notifications may not be scheduled in the past."]}
        return $response;
    }

    /**
     * cancel app push
     *
     * @param  String $push_id
     * @return \Illuminate\Http\Response
     */
    public function cancel($push_id)
    {
        $fields = array(
            'app_id' => config('services.onesignal.app_id')
        );

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications/" . $push_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . config('services.onesignal.api_key')
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        // {"success":true}
        return $response;
    }
    
}
