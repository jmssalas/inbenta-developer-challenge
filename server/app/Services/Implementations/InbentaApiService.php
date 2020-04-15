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

    private $createConversationEndpoint;
    private $sendMessageEndpoint;

    function __construct()
    {
        $this->apiKey = config('inbenta.apiKey');
        $this->secret = config('inbenta.secret');

        $this->authEndpoint = config('inbenta.endpoints.auth');
        $this->apiEndpoint = config('inbenta.endpoints.api');
        
        $this->createConversationEndpoint = config('inbenta.endpoints.chatbot.create_conversation');
        $this->sendMessageEndpoint = config('inbenta.endpoints.chatbot.send_message');
    }

    public function connect() 
    {
        $headers = [
            'x-inbenta-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
        $body = [
            'secret' => $this->secret
        ];

        $response = Http::withHeaders($headers)->post($this->authEndpoint, $body);
        $this->processResponse($response);

        $json = $response->json();

        $accessToken = $json['accessToken'];
        $expiration = $json['expiration'];

        $headers = [
            'x-inbenta-key' => $this->apiKey,
            'Authorization' => 'Bearer ' . $accessToken,
        ];
        
        $response = Http::withHeaders($headers)->get($this->apiEndpoint, $headers);
        $this->processResponse($response);
        
        $json = $response->json();

        $chatbotApiUrl = $json['apis']['chatbot'];

        $data = [
            'accessToken' => $accessToken,
            'expiration' => $expiration,
            'chatbotApiUrl' => $chatbotApiUrl,
        ];

        session()->put($data);
        //return $data; // Return $data if needed 
    }

    public function createConversation()
    {
        $data = $this->checkConnection();

        $headers = [
            'x-inbenta-key' => $this->apiKey,
            'Authorization' => 'Bearer ' . $data['accessToken'],
        ];
        $body = [];

        $response = Http::withHeaders($headers)->post($data['chatbotApiUrl'] . $this->createConversationEndpoint, $body);
        $this->processResponse($response);

        $json = $response->json();

        $session = [
            'sessionToken' => $json['sessionToken'],
            'sessionId' => $json['sessionId'],
        ];
        session()->put($session);
        //$data = array_merge($data, $session);
        //return $data; // Return $data if needed
    }

    private function checkConnection() 
    {
        Log::info('Getting session info...');
        $totalTry = 2;
        $try = 0;
        $expiration = null;
        while (!$expiration && $try < $totalTry) 
        {
            $expiration = session()->get('expiration');

            if (!$expiration)
            {
                Log::info('Session info not found. Getting new session info...');
                $this->connect();
                Log::info('Session info renewed.');
            }
            $try++;
        }

        if (!$expiration) throw new ApiException(__('inbenta.session_error'), 500);

        Log::info('Checking expiration...');
        $expirationDate = Carbon::createFromTimestamp($expiration);

        if (Carbon::now() > $expirationDate) 
        {
            Log::info('Token has expired. Getting new token...');
            $this->connect();
            Log::info('Token renewed.');
        }
        else Log::info('Token is still valid.');

        return [
            'accessToken' => session()->get('accessToken'),
            'chatbotApiUrl' => session()->get('chatbotApiUrl'),
        ];
    }

    private function processResponse($response) 
    {
        if (!$response->successful()) 
            throw new ApiException(
                        $this->customErrorMessage($response->json()['message']), 
                        $response->status());
    }

    private function customErrorMessage($message) 
    {
        return __('inbenta.api_error') . $message;
    }
}
