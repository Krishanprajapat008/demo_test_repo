<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class laraController extends Controller
{
    

    public function lara_api()
    {
        $url = 'https://jsonplaceholder.typicode.com/posts';

        
        $response_data = Http::withOptions(['verify' => false])->get($url);

        $response_data = json_decode($response_data);
      

        return view('laraApi',compact('response'));

    }
}
