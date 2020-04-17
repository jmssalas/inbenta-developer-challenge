<?php 

namespace App\Services\Interfaces;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\SendMessageRequest;

use Carbon\Carbon;

interface InbentaApiInterface 
{
    
    public function sendMessage($message);

    public function getHistory();

}
