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

    public function sendMessage($message) 
    {
        $success = $newConversation = false;
        $totalTry = 2;
        $try = 0;
        do 
        {
            $data = $this->checkConversation($newConversation);

            $headers = [
                'x-inbenta-key' => $this->apiKey,
                'x-inbenta-session' => 'Bearer ' . $data['sessionToken'] . "npand",
                'Authorization' => 'Bearer ' . $data['accessToken'],
            ];

            $body = ['message' => $message];

            $response = Http::withHeaders($headers)->post($data['chatbotApiUrl'] . $this->sendMessageEndpoint, $body);
            if (!$response->successful()) $newConversation = true;
            else $success = true;

            $try++;
        } while (!$success && $try < $totalTry);
        
        if (!$success) $this->processResponse($response);

        $answers = $response->json()['answers'];

        $messages = [];
        foreach ($answers as $answer)
        {
            foreach ($answer['messageList'] as $message)
            {
                $messages[] = $message;
            }
        }

        return [
            'response' => $messages,
        ];
    }

    private function checkConversation($newConversation = false)
    {
        if (!$newConversation) 
        {
            Log::info('Getting conversation session info...');
            $totalTry = 2;
            $try = 0;
            $sessionToken = null;
            while (!$sessionToken && $try < $totalTry) 
            {
                $sessionToken = session()->get('sessionToken');

                if (!$sessionToken)
                {
                    Log::info('Session info not found. Getting new session info...');
                    $this->createConversation();
                    Log::info('Session info renewed.');
                }
                $try++;
            }
        }
        else 
        {
            Log::info('Renewing session info...');
            $this->createConversation();
            Log::info('Session info renewed.');

            $sessionToken = session()->get('sessionToken');
        }
        
        if (!$sessionToken) throw new ApiException(__('inbenta.session_error'), 500);

        Log::info('Conversation info got!');

        $this->checkExpiration();

        return [
            'accessToken' => session()->get('accessToken'),
            'chatbotApiUrl' => session()->get('chatbotApiUrl'),
            'sessionToken' => session()->get('sessionToken'),
        ];
    }

    private function checkConnection() 
    {
        Log::info('Getting token session info...');
        $totalTry = 2;
        $try = 0;
        $accessToken = null;
        while (!$accessToken && $try < $totalTry) 
        {
            $accessToken = session()->get('accessToken');

            if (!$accessToken)
            {
                Log::info('Session info not found. Getting new session info...');
                $this->connect();
                Log::info('Session info renewed.');
            }
            $try++;
        }

        if (!$accessToken) throw new ApiException(__('inbenta.session_error'), 500);

        $this->checkExpiration();

        return [
            'accessToken' => session()->get('accessToken'),
            'chatbotApiUrl' => session()->get('chatbotApiUrl'),
        ];
    }

    private function checkExpiration()
    {
        Log::info('Checking expiration...');
        $expirationDate = Carbon::createFromTimestamp(session()->get('expiration'));

        if (Carbon::now() > $expirationDate) 
        {
            Log::info('Token has expired. Getting new token...');
            $this->connect();
            Log::info('Token renewed.');
        }
        else Log::info('Token is still valid.');
    }

    private function processResponse($response) 
    {
        if (!$response->successful()) 
        {
            $json = $response->json();
            $message = "";

            if (array_key_exists('message', $json)) $message = $json['message'];
            elseif (array_key_exists('errors', $json)) 
            {
                foreach($json['errors'] as $error)
                    if (array_key_exists('message', $error)) $message = $message . " " . $error['message'];
            }
            else $message = json_encode($json);

            throw new ApiException(
                        $this->customErrorMessage($message), 
                        $response->status());
        }
    }

    private function customErrorMessage($message) 
    {
        return __('inbenta.api_error') . $message;
    }
}
