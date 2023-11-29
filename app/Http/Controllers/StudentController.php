<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Student_data;

class StudentController extends Controller
{
    
    public function index()
    {
        $data = array('name' => 'krish', 'city' => 'sirohi','dept' => 'admin');

        $stu_data = new Student_data();

        $student = new Student();

        $student->name = $data['name'];
        $student->city = $data['city'];
        $student->status = '1';
        $student->dept = $data['dept'];

        $student->save();

        $student->studentData()->save($stu_data);
    }
}
