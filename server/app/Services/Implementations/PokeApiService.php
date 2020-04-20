<?php 

namespace App\Services\Implementations;

use App\Exceptions\ApiException;
use App\Services\Interfaces\PokeApiInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PokeApiService implements PokeApiInterface
{
    private $url;
    private $pokemonEndpoint;
    private $locationEndpoint;

    private $limit;
    private $offsetRandom;
    private $offsetStart;
    private $offsetEnd;

    function __construct()
    {
        $this->url = config('pokeapi.url');
        $this->pokemonEndpoint = config('pokeapi.endpoints.pokemon');
        $this->locationEndpoint = config('pokeapi.endpoints.location');

        if (!$this->url || !$this->pokemonEndpoint || !$this->locationEndpoint)
        {
            throw new ApiException(__('general.pokeapi_env_variables_not_set'), 500);
        }
    }

    public function getPokemons($limit = null, $offset = null)
    {
        $params = $this->getParams($limit, $offset);
        $response = $this->makeGetRequest($this->url . $this->pokemonEndpoint . $params);
        $this->processResponse($response);

        $json = $response->json();

        $pokemons = [];
        try {
            foreach ($json['results'] as $pokemon) $pokemons[] = $pokemon['name'];
        } catch (Exception $exception) 
        {
            //TODO PokeAPI has changed the responses of endpoints
            // For now, return an API Exception
            throw new ApiException(__('pokeapi.api_responses_changed'), 400);
        }
        
        return $pokemons;
    }

    public function getLocations($limit = null, $offset = null)
    {
        $params = $this->getParams($limit, $offset);
        $response = $this->makeGetRequest($this->url . $this->locationEndpoint . $params);
        $this->processResponse($response);

        $json = $response->json();

        $locations = [];
        try {
            foreach ($json['results'] as $location) $locations[] = $location['name'];
        } catch (Exception $exception) 
        {
            //TODO PokeAPI has changed the responses of endpoints
            // For now, return an API Exception
            throw new ApiException(__('pokeapi.api_responses_changed'), 400);
        }
        
        return $locations;
    }

    private function getParams($limit, $offset) 
    {
        return "?limit=" . $limit . "&offset=" . $offset;
    }

    private function makeGetRequest($endpoint) 
    {
        Log::debug("Making GET request to " . $endpoint);
        $response = Http::get($endpoint);
        Log::debug("Successful: " . json_encode($response->successful()));
        return $response;
    }

    private function processResponse($response) 
    {
        if (!$response->successful()) 
        {
            throw new ApiException(__('pokeapi.api_error'), $response->status());
        }
    }

}
