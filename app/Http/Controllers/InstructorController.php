<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Submission;

class InstructorController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'message' => 'Welcome Instructor',
        ]);
    }

    public function getStats()
    {
        $instructorId = auth()->id();

        $examIds = Exam::where('user_id', $instructorId)->pluck('id');
        $totalExams = $examIds->count();

        $activeStudents = Submission::whereIn('exam_id', $examIds)
            ->distinct('user_id')
            ->count('user_id');

        $totalSubmissions = Submission::whereIn('exam_id', $examIds)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_exams' => $totalExams,
                'active_students' => $activeStudents,
                'total_submissions' => $totalSubmissions,
            ],
        ]);
    }
}
