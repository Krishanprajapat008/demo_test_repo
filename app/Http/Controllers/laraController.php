<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class laraController extends Controller
{
    

    public function lara_api()
    {
        $url = 'https://jsonplaceholder.typicode.com/posts';

        
        $response = Http::withOptions(['verify' => false])->get($url);

        $response = json_decode($response);
      

        return view('laraApi',compact('response'));

        // 'test';
    }
}
