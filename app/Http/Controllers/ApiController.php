<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Api;
use Illuminate\Support\Facades\Http;


class ApiController extends Controller
{
    

    public function get_api($userId)
    {
            $data = Api::where('userId',$userId)->get()->toArray();
            //$data = array("name" => "krishan", "city" => "Pindwara");
            return response()->json($data);
            
    }

    public function post_api(Request $request)
    {

        $data = new Api();

        $data->userId = $request->userId;
        $data->title = $request->title;
        $data->body = $request->body;

        $data->save();

        return response()->json([
                'Msg'       => 'Records saved success fully',
                'Success'   => 'Successfully'
        ]);
    }

    public function del_api($id)
    {
        $data = Api::find($id)->delete();

        return response()->json([
            'Msg' => 'Records Deleted Successfully'
        ]);
    }

    public function update_api(Request $request, $id)
    {
            $data = Api::find($id)->first();

            $data->userId = $request->userId;
            $data->title = $request->title;
            $data->body = $request->body;

            $data->save();

            return response()->json([
                'Msg' => 'Records Updated Successfully'
            ]);

    }


    public function test()
    {
        $url    = "https://jsonplaceholder.typicode.com/posts";
        //$response = Http::get("https://dummyjson.com/products");

        $response = Http::withOptions(['verify' => false])->get($url);

        $posts = $response->json();

        // var_dump($posts);exit;

        
        foreach($posts as $post)
        {
                $api = new Api();
                $api->userId    = $post['userId'];
                $api->title     = $post['title'];
                $api->body      = $post['body'];
                $api->save();            
        }

        
        //2 way to insert data use both way

        // foreach($posts as $post)
        // {
        //     Api::create([
        //         'userId'    => $post['userId'],
        //         'title'     => $post['title'],
        //         'body'      => $post['body']
        //     ]);           
        // }

        $return = array('msg','successfully addedd');

        dd($return);
        
    }
}
