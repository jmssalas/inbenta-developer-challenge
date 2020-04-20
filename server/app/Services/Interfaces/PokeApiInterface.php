<?php 

namespace App\Services\Interfaces;

interface PokeApiInterface 
{
	
    public function getPokemons($limit = null, $offset = null);

    public function getLocations($limit = null, $offset = null);

}
