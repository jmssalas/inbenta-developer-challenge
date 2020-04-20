<?php 

namespace App\Services\Interfaces;

interface InbentaApiInterface 
{
    
    public function sendMessage($message);

    public function getHistory();

}
