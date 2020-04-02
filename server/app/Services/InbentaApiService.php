<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class InbentaApiService 
{
    private $apiKey;
    private $secret;
    private $authEndpoint;
    private $apiEndpoint;

    function __construct()
    {
        $this->apiKey = config('inbenta.apiKey');
        $this->secret = config('inbenta.secret');

        $this->authEndpoint = config('inbenta.endpoints.auth');
        $this->apiEndpoint = config('inbenta.endpoints.api');
    }

    public function connect() 
    {
        $headers = [
            'x-inbenta-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ];
        $body = [
            'secret' => $this->secret
        ];

        $response = Http::withHeaders($headers)->post($this->authEndpoint, $body);
        if (!$response->successful()) abort($response->status());

        $json = $response->json();

        $accessToken = $json['accessToken'];
        $expiration = $json['expiration'];

        $headers = [
            'x-inbenta-key' => $this->apiKey,
            'Authorization' => 'Bearer ' . $accessToken
        ];
        
        $response = Http::withHeaders($headers)->get($this->apiEndpoint, $headers);
        if (!$response->successful()) abort($response->status());
        
        $json = $response->json();

        $chatbotApiUrl = $json['apis']['chatbot'];

        return [
            'accessToken' => $accessToken,
            'expiration' => $expiration,
            'chatbotApiUrl' => $chatbotApiUrl,
        ];
    }
}
