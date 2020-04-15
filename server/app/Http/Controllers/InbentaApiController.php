<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interfaces\InbentaApiInterface;
use App\Http\Requests\SendMessageRequest;

class InbentaApiController extends Controller
{
	protected $inbentaApiService;

    function __construct(InbentaApiInterface $inbentaApiService)
    {
    	$this->inbentaApiService = $inbentaApiService;
    }

    public function connect() 
    {
    	return $this->inbentaApiService->connect();
    }

    public function createConversation() 
    {
    	return $this->inbentaApiService->createConversation();
    }

    public function sendMessage(SendMessageRequest $request) 
    {
    	return $request;
    	return $this->inbentaApiService->sendMessage($request->message);
    }
}
