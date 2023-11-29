<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\myevent;
use App\Mail\testEmail;
use Illuminate\Mail\Mailable;

class mylistener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {

        //$data = array('name' => 'krishan','city'=>'pindwara','email' =>'kprajapat008@gmail.com');

        \Mail::to('kprajapat008@gmail.com')->send(new testEmail());

        // Mail::send('myemail',$data,function($msg) use ($data){
        //     $msg->to($data['email']);
        //     $msg->subject($data['name']);
        // });

    }
}
