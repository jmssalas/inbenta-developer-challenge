<?php 

namespace App\Services\Implementations;

use App\Exceptions\ApiException;
use App\Services\Interfaces\InbentaApiInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class InbentaApiService implements InbentaApiInterface
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
        if (!$response->successful()) throw new ApiException($this->customErrorMessage($response->json()['message']), $response->status());

        $json = $response->json();

        $accessToken = $json['accessToken'];
        $expiration = $json['expiration'];

        $headers = [
            'x-inbenta-key' => $this->apiKey,
            'Authorization' => 'Bearer ' . $accessToken
        ];
        
        $response = Http::withHeaders($headers)->get($this->apiEndpoint, $headers);
        if (!$response->successful()) throw new ApiException($this->customErrorMessage($response->json()['message']), $response->status());
        
        $json = $response->json();

        $chatbotApiUrl = $json['apis']['chatbot'];

        $data = [
            'accessToken' => $accessToken,
            'expiration' => $expiration,
            'chatbotApiUrl' => $chatbotApiUrl,
        ];

        session()->put($data);
    }

    private function customErrorMessage($message) 
    {
        return __('inbenta.api_error') . $message;
    }
}
