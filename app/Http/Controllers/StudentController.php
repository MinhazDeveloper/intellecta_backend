<?php

namespace App\Http\Controllers;

class StudentController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'message' => 'Welcome Student dashboard',
        ]);
    }
}
