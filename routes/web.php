<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\LaraController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


// Route::middleware('emid')->group(function(){
//     Route::get('event',[EventController::class,'event']);
//     Route::get('add-event',[EventController::class,'add_event']);
// });

Route::controller(EventController::class)->group(function(){
    Route::get('event','event');
    Route::get('add-event','add_event');
    Route::get('e-call','e_call');
});

Route::get('students',[StudentController::class,'index']);

Route::get('send-email2',function(){

    $data = 'kprajapat008@gmail.com';

    dispatch(new App\Jobs\SendEmailJob());

    dd('Email send Successfully');

});

Route::get('lara-api',[LaraController::class,'lara_api']);


// Route::get('test',[ApiController::class,'test']);
