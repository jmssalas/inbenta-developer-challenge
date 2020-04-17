<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inbenta Environment Variables
    |--------------------------------------------------------------------------
    */

    'apiKey' => env('INBENTA_API_KEY', null),
    'secret' => env('INBENTA_SECRET', null),

    'endpoints' => [
        'auth' => env('INBENTA_ENDPOINT_AUTH', null),
        'api' => env('INBENTA_ENDPOINT_API', null),

        'chatbot' => [
            'create_conversation' => env('INBENTA_ENDPOINT_CHATBOT_CREATE_CONVERSATION', null), 
            'send_message' => env('INBENTA_ENDPOINT_CHATBOT_SEND_MESSAGE', null), 
            'get_history' => env('INBENTA_ENDPOINT_CHATBOT_GET_HISTORY', null), 
        ],
    ],
    
];
