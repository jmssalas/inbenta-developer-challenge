<?php 

namespace App\Services\Interfaces;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

interface InbentaApiInterface 
{
    public function connect();

    public function createConversation();
    
}
