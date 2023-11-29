<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\myevent;

class EventController extends Controller
{
    public function event()
    {
       $ar = array('name' => 'krishan', 'age' => 60);

       dd($ar);
    }

    public function add_event()
    {
        echo 'ts';
    }

    public function e_call()
    {
        event (new myevent('test'));

        return redirect('event');
    }
}
