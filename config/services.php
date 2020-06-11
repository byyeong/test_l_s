<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'travel_default_img' => array(
        '/travel/default/default_1.jpg',
        '/travel/default/default_2.jpg',
        '/travel/default/default_3.jpg', 
        '/travel/default/default_4.jpg',
        '/travel/default/default_5.jpg',
        '/travel/default/default_6.jpg',
        '/travel/default/default_7.jpg',
        '/travel/default/default_8.jpg',
        '/travel/default/default_9.jpg',
        '/travel/default/default_10.jpg',
        '/travel/default/default_11.jpg',
        '/travel/default/default_12.jpg'
    ),

    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'rest_api_key' => env('ONESIGNAL_REST_API_KEY')
    ],

    'tool_personalization' => [
        'base' => 'base',
        'custom' => 'custom',
        'activity' => 'activity',
    ],

    'tool_delete' => [
        'base' => 0,
        'custom' => 1
    ],

    'tool_checked' => [
        'checked' => 1,
        'unchecked' => 0
    ],

    'tool_model' => [
        'todo' =>
        'App\Models\TravelTodo',
        'packing' => 'App\Models\TravelPacking',
        'note' => 'App\Models\Notes',
        'diary' => 'App\Models\Diary',
        'wallet' => 'App\Models\Wallet'
    ],

    'tool_name' => [
        'todo' => '할일',
        'packings' => '짐싸기',
        'note' => '노트',
    ],

    'tool_type' => [
        'todo' => 'todo',
        'packings' => 'packings',
        'note' => 'note',
    ],

    'tool_category_id' => [
        'base' => 1,
        'activity' => 2,
        'custom' => 3,
    ],

    'tool_show' => [
        'show' => 1,
        'hidden' => 0
    ],

    'default_Admin' => [
        'id' => 0
    ],

    'file_limit' => [
        'size' => 10240
    ],

    'server_url' => [
        'admin' =>env('APP_ADMIN_URL')
    ],

    'loda_card' => [
        'todo' => 'todo',
        'info' => 'info',
        'weather' => 'weather',
        'dday' => 'dday',
        'event' => 'event',
        'goods' => 'goods',
        'start' => 'start',
        'welcome' => 'welcome',
        'any' => 'any'
    ],

    'loda_card_id' => [
        'welcome' => 1,
        'start' => 2,
        'visa' => 5,
        'passport' => 9,
        'weather_first' => 22
    ],

    'push' => [
        'time' => '09:05:00'
    ],

    'sns_type' => [
        'KakaoAK' => 'kakao'
    ],

    'start_card' => [
        'original_title' => '<b>오늘부터</b> 함께,<br><b>여행준비</b> 시작!',
        'original_contents' => '복잡하고 힘든 여행준비.<br>로다와 함께 준비해 보세요. 챙겨야 할 일을 그때 그때 알려드릴께요.',
        'miss_title' => '<b>오늘부터</b> 함께,<br><b>여행준비</b> 시작!',
        'miss_contents' => '시간이 얼마 남지 않았네요.<br>몇가지 할일은 이미 확인했어야 할 시기가 지나버렸는지도 모르겠어요.',
        'last_title' => '<b>여행 출발일이<br>임박 했어요.</b>',
        'last_contents' => '곧 떠나시는군요!<br>로다가 그때 그때 챙겨 드릴려고 했는데...<br>그동안 여행 준비를 잘 했는지 마지막으로 확인해 볼 때에요.',
    ],

    'onesignal_tags' => [
        'mkp' => 'marketing_push'
    ],

    'used_type' => [
        '음식', 
        '쇼핑',
        '관광',
        '교통',
        '숙박',
        '기타'
    ],

    'traveling_loda_card_push_comments' => [
        'title' => '즐거운 일이 많은 여행이 되길 바라요.',
        'contents' => '오늘은 무슨 일이 있었나요?'
    ],

    // 일자
    'days' => [
        '첫날', '둘째날', '셋째날', '넷째날', '다섯째날', '여섯째날', '일곱째날', '여덟째날', '아홉째날', '열째날',
        '열한째날', '열두째날', '열셋째날', '열넷째날', '열다섯째날', '열여섯째날', '열일곱째날', '열여덟째날', '열아홉째날', '스무째날',
        '스물한째날', '스물두째날', '스물셋째날', '스물넷째날', '스물다섯째날', '스물여섯째날', '스물일곱째날', '스물여덟째날', '스물아홉째날', '서른째날',
    ],

    // ing card title
    'ing_title_first' => '<b>여행 첫날</b><br>어떠셨어요?',
    'ing_contents_first' => '설레는 여행 첫날,<br>오늘은 무슨 일이 있었나요?',
    'ing_title' => '<b>여행 ' . $order . '</b>은<br>어떠셨어요?',
    'ing_contents' => '즐거운 일이 많은 여행이 되길 바라요.<br>오늘은 무슨 일이 있었나요?',
    'ing_title_last' => '<b>즐거운 여행</b><br><b>되셨나요?</b><br>이번 여행을 정리해요.',
    'ing_contents_last' => '아무쪼록 즐거운 여행이<br>되셨기를 바라요.',

    'weather_comments' => [
        '01' => ['화창하고', '예쁜 사진 많이 찍어요! 📸'],
        '02' => ['대체로 맑고', '여행 하기 괜찮을 거에요👌'],
        '03' => ['구름이 좀 많고', '조금 흐릴 뿐이에요. 즐거운 여행 되세요~ 👏'],
        '04' => ['흐리고', '아쉽지만, 흐린 날만의 매력을 찾아보죠. ☝️'],
        '09' => ['비 소식이 있고', '작은 우산이나 접히는 우비를 챙기는게 좋겠어요. 🌂'],
        '10' => ['비가 올 것 같고', '비 내리는 운치를 즐겨볼까요? ☔️'],
        '11' => ['우르르 쾅쾅!', '실내 위주의 관광 코스를 정하는 것이 좋겠어요. ⚡️'],
        '13' => ['눈 소식이 있고', '미끄러운 길 조심. 따뜻하게 입으세요! ❄️'],
        '50' => ['안개가 자욱하고', '산이나 먼 경치를 즐기는 여행지는 피해도 좋겠어요. 🌫']
    ],

    'weather_comments_temp' => [
        '매우 추워요.', '추울 것 같아요.', '쌀쌀할 것 같아요.', '선선할 것 같아요.', '따뜻할 것 같아요.', '더울 것 같아요.', '매우 더워요.'
    ],

    'currency_rep' => [
        'KRW' => 135
    ]

    
];
