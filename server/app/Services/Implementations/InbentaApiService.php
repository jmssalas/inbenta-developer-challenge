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
    private $getHistoryEndpoint;

    function __construct()
    {
        $this->apiKey = config('inbenta.apiKey');
        $this->secret = config('inbenta.secret');

        $this->authEndpoint = config('inbenta.endpoints.auth');
        $this->apiEndpoint = config('inbenta.endpoints.api');
        
        $this->createConversationEndpoint = config('inbenta.endpoints.chatbot.create_conversation');
        $this->sendMessageEndpoint = config('inbenta.endpoints.chatbot.send_message');
        $this->getHistoryEndpoint = config('inbenta.endpoints.chatbot.get_history');

        if (!$this->apiKey || !$this->secret || !$this->authEndpoint ||
            !$this->apiEndpoint || !$this->createConversationEndpoint || 
            !$this->sendMessageEndpoint || !$this->getHistoryEndpoint) 
        {
            throw new ApiException(__('general.inbenta_env_variables_not_set'), 500);
        }
    }

    public function sendMessage($message) 
    {
        $success = $newConversation = false;
        $totalTry = 2;
        $try = 0;
        while (!$success && $try < $totalTry) 
        {   // This loop is because the sessionToken can expire and there is no expiration value. (like in accessToken)
            // If the first time the call has failed, make a call to create a new conversation. 
            // if the second time the call has failed again, then return the error.

            $data = $this->checkConversation($newConversation);

            $headers = [
                'x-inbenta-key' => $this->apiKey,
                'x-inbenta-session' => 'Bearer ' . $data['sessionToken'],
                'Authorization' => 'Bearer ' . $data['accessToken'],
            ];

            $body = ['message' => $message];

            $response = $this->makePostRequest($headers, $data['chatbotApiUrl'] . $this->sendMessageEndpoint, $body);
            if (!$response->successful()) $newConversation = true;
            else $success = true;

            $try++;
        }
        
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

    public function getHistory() 
    {
        $accessToken = session()->get('accessToken');
        $chatbotApiUrl = session()->get('chatbotApiUrl');
        $sessionToken = session()->get('sessionToken');

        $history = [];
        if ($accessToken && $chatbotApiUrl && $sessionToken)
        {
            $headers = [
                'x-inbenta-key' => $this->apiKey,
                'x-inbenta-session' => 'Bearer ' . $sessionToken,
                'Authorization' => 'Bearer ' . $accessToken,
            ];

            $response = $this->makeGetRequest($headers, $chatbotApiUrl . $this->getHistoryEndpoint);
            if ($response->successful()) $history = $response->json();
        }

        return [
            "history" => $history,
        ];
    }

    private function connect() 
    {
        $headers = [
            'x-inbenta-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
        $body = [
            'secret' => $this->secret
        ];

        $response = $this->makePostRequest($headers, $this->authEndpoint, $body);
        $this->processResponse($response);

        $json = $response->json();

        $accessToken = $json['accessToken'];
        $expiration = $json['expiration'];

        $headers = [
            'x-inbenta-key' => $this->apiKey,
            'Authorization' => 'Bearer ' . $accessToken,
        ];
        
        $response = $this->makeGetRequest($headers, $this->apiEndpoint);
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

    private function createConversation()
    {
        $data = $this->checkConnection();

        $headers = [
            'x-inbenta-key' => $this->apiKey,
            'Authorization' => 'Bearer ' . $data['accessToken'],
        ];
        $body = [];

        $response = $this->makePostRequest($headers, $data['chatbotApiUrl'] . $this->createConversationEndpoint, $body);
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
            Log::info('Something wrong with last conversation session. Renewing conversation session info...');
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

    private function makePostRequest($headers, $endpoint, $body) 
    {
        Log::debug("Making POST request to " . $endpoint);
        Log::debug("\t- headers: " . json_encode($headers));
        Log::debug("\t- body: " . json_encode($body));
        $response = Http::withHeaders($headers)->post($endpoint, $body);
        Log::debug("Successful: " . json_encode($response->successful()));
        return $response;
    }

    private function makeGetRequest($headers, $endpoint) 
    {
        Log::debug("Making GET request to " . $endpoint);
        Log::debug("\t- headers: " . json_encode($headers));
        $response = Http::withHeaders($headers)->get($endpoint);
        Log::debug("Successful: " . json_encode($response->successful()));
        return $response;
    }


    private function customErrorMessage($message) 
    {
        return __('inbenta.api_error') . $message;
    }
}
