<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inbenta Environment Variables
    |--------------------------------------------------------------------------
    */

    'apiKey' => env('INBENTA_API_KEY'),
    'secret' => env('INBENTA_SECRET'),

    'endpoints' => [
        'auth' => env('INBENTA_ENDPOINT_AUTH'),
        'api' => env('INBENTA_ENDPOINT_API'),

        'chatbot' => [
            'create_conversation' => env('INBENTA_ENDPOINT_CHATBOT_CREATE_CONVERSATION'), 
            'send_message' => env('INBENTA_ENDPOINT_CHATBOT_SEND_MESSAGE'), 
        ],
    ],
    
];
