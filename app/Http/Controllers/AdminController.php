<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Exam;
use App\Models\Submission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function userAll(Request $request)
    {
        $user_all = User::select([
            'id',
            'name',
            'email',
            'role',
            'email_verified_at',
            'last_login_at',
            'created_at',
        ])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Hello admin',
            'data' => $user_all,
        ]);
    }

    /** Save users */
    public function storeUser(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['admin', 'student', 'instructor'])],
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'role' => $fields['role'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    /** update user */
    public function updateUser(Request $request, $id)
    {

        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'role' => ['required', Rule::in(['admin', 'student', 'instructor'])],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|min:6',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user,
        ], 200);
    }

    /** delete user */ 
    public function destroy($id)
    {
        try {

            if (auth()->id() == $id) {
                return response()->json(['success' => false, 'message' => 'You cannot delete yourself!'], 403);
            }

            $user = User::find($id);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /** dashboard stats */
    public function getStats()
    {
        $totalStudents = User::where('role', 'student')->count();
        $totalExams = Exam::count();

        $registrations = User::where('role', 'student')
            ->where('created_at', '>=', now()->subDays(6))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayName = now()->subDays($i)->format('D');

            $found = $registrations->where('date', $date)->first();

            $chartData[] = [
                'day' => $dayName,
                'count' => $found ? $found->count : 0,
            ];
        }

        $examsToday = Exam::whereDate('updated_at', today())
            ->get()
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'duration_minutes' => $exam->duration_minutes,
                    'total_marks' => $exam->total_marks ?? 0,
                    'time' => $exam->start_time
                                ? Carbon::parse($exam->start_time)->format('h:i A')
                                : null,
                ];

            });

        $recentActivities = ActivityLog::with('user:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($log) {
                return [
                    'user_name' => $log->user->name ?? 'N/A',
                    'action' => $log->action,
                    'ip_address' => $log->ip_address,
                    'created_at_human' => $log->created_at->diffForHumans(),
                    'status' => $log->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_students' => $totalStudents,
                'total_exams' => $totalExams,
                'registration_trend' => $chartData,
                'exams_today' => $examsToday,
                'recent_activities' => $recentActivities,
            ],
        ], 200);
    }

    /**  get all submissions result for admin */
    public function getAllSubmissions()
    {
        $submissions = Submission::with(['user:id,name,email', 'exam:id,title,pass_marks,total_marks'])
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $submissions->map(function ($submission) {

            $passMarks = $submission->exam->pass_marks ?? 0;
            $isPass = $submission->obtained_marks >= $passMarks;

            return [
                'id' => $submission->id,
                'student_name' => $submission->user->name,
                'student_email' => $submission->user->email,
                'exam_title' => $submission->exam->title,
                'total_marks' => $submission->exam->total_marks,
                'pass_marks' => $submission->exam->pass_marks,
                'obtained_marks' => $submission->obtained_marks,
                'status' => $isPass ? 'Pass' : 'Fail',
                'submitted_at' => $submission->created_at->format('d M Y, h:i A'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
