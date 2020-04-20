<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PokeAPI Environment Configuration
    |--------------------------------------------------------------------------
    */

    'url' => env('POKEAPI_URL', null),

    'endpoints' => [
        'pokemon' => env('POKEAPI_ENDPOINT_POKEMON', null),
        'location' => env('POKEAPI_ENDPOINT_LOCATION', null),
    ],

    'pagination' => [
        'limit' => env('POKEAPI_PAGINATION_LIMIT'),
    ],
    
];
