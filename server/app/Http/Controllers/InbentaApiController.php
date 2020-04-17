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

    public function sendMessage(SendMessageRequest $request) 
    {
    	return $this->inbentaApiService->sendMessage($request->message);
    }

	public function getHistory() 
    {
    	return $this->inbentaApiService->getHistory();
    }
}
