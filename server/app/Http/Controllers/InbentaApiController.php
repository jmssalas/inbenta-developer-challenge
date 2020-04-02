<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InbentaApiService;

class InbentaApiController extends Controller
{
	protected $inbentaApiService;

    function __construct(InbentaApiService $inbentaApiService)
    {
    	$this->inbentaApiService = $inbentaApiService;
    }

    public function connection() 
    {
    	return $this->inbentaApiService->connect();
    }
}
