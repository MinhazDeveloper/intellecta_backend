<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Option;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExamController extends Controller
{
    public function index()
    {
        $instructorId = auth()->id();

        $exams = Exam::where('user_id', $instructorId)
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $exams,
        ], 200);
    }

    // get exam list for student
    public function getExamlist()
    {
        $exams = Exam::select('id', 'title', 'subject', 'duration_minutes', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->withCount('questions')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $exams,
        ], 200);
    }

    /**
     * Store a newly created exam in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subject' => 'nullable|string',
            'total_marks' => 'required|integer|min:1',
            'pass_marks' => 'required|integer|lte:total_marks',
            'duration_minutes' => 'required|integer|min:1',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['user_id'] = auth()->id();

        $exam = Exam::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Exam created successfully!',
            'data' => $exam,
        ], 201);
    }

    /**
     * Display the specified exam.
     */
    public function show($id)
    {
        $exam = Exam::find($id);

        if (! $exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $exam], 200);
    }

    /**
     * Update the specified exam in storage.
     */
    public function update(Request $request, $id)
    {
        $exam = Exam::find($id);

        if (! $exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'total_marks' => 'integer|min:1',
            'pass_marks' => 'integer|lte:total_marks',
            'duration_minutes' => 'integer|min:1',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $exam->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Exam updated successfully!',
            'data' => $exam,
        ], 200);
    }

    /**
     * Remove the specified exam from storage.
     */
    public function destroy($id)
    {
        $exam = Exam::find($id);

        if (! $exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $exam->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam deleted successfully!',
        ], 200);
    }

    // student exam submit
    public function ExamSubmit(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.selected_option_id' => 'nullable|exists:options,id',
            'time_taken' => 'nullable|integer',
        ]);

        $userId = auth()->id();

        if (Submission::where('user_id', $userId)->where('exam_id', $validated['exam_id'])->exists()) {
            return response()->json(['success' => false, 'message' => 'You have already submitted this exam!'], 400);
        }

        try {
            return DB::transaction(function () use ($validated, $userId) {

                $questionIds = collect($validated['answers'])->pluck('question_id');
                $correctOptionsMap = Option::whereIn('question_id', $questionIds)
                    ->where('is_correct', true)
                    ->pluck('id', 'question_id');

                $correctCount = 0;
                $wrongCount = 0;
                $answersToInsert = [];

                $submission = Submission::create([
                    'user_id' => $userId,
                    'exam_id' => $validated['exam_id'],
                    'total_questions' => count($validated['answers']),
                    'time_taken' => $validated['time_taken'] ?? 0,
                    'obtained_marks' => 0,
                    'correct_answers' => 0,
                    'wrong_answers' => 0,
                ]);

                foreach ($validated['answers'] as $ans) {
                    $correctOptionId = $correctOptionsMap[$ans['question_id']] ?? null;
                    $isCorrect = ($ans['selected_option_id'] != null && $ans['selected_option_id'] == $correctOptionId);
                    
                    if ($ans['selected_option_id']) {
                        $isCorrect ? $correctCount++ : $wrongCount++;
                    }

                    $answersToInsert[] = [
                        'submission_id' => $submission->id,
                        'question_id' => $ans['question_id'],
                        'option_id' => $ans['selected_option_id'],
                        'is_correct' => $isCorrect,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                SubmissionAnswer::insert($answersToInsert);

                $submission->update([
                    'obtained_marks' => $correctCount,
                    'correct_answers' => $correctCount,
                    'wrong_answers' => $wrongCount,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Exam submitted successfully!',
                    'result' => [
                        'score' => $correctCount,
                        'total' => count($validated['answers']),
                        'submission_id' => $submission->id,
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to submit: '.$e->getMessage()], 500);
        }
    }

    // student exam result
    public function getResult($id)
    {
        $userId = auth()->id();

        $submission = Submission::with(['exam:id,title,total_marks'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $submission) {
            return response()->json(['message' => 'Result not found'], 404);
        }

        $details = SubmissionAnswer::with([
            'question:id,question_text',
            'question.options:id,question_id,option_text,is_correct',
        ])
            ->where('submission_id', $id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'exam_title' => $submission->exam->title,
                'total_questions' => $submission->total_questions,
                'correct_answers' => $submission->correct_answers,
                'wrong_answers' => $submission->wrong_answers,
                'obtained_marks' => $submission->obtained_marks,
                'time_taken' => $submission->time_taken,
                'submitted_at' => $submission->created_at->format('d M Y, h:i A'),
                'answers' => $details->map(function ($ans) {
                    return [
                        'question' => $ans->question->question_text,
                        'selected_option_id' => $ans->option_id,
                        'is_correct' => $ans->is_correct,
                        'options' => $ans->question->options, // সব অপশন পাঠাচ্ছি রিভিউ দেখানোর জন্য
                    ];
                }),
            ],
        ], 200);
    }

    // Exam history for student
    public function getExamHistory()
    {
        $userId = auth()->id();

        $history = Submission::with('exam:id,title,pass_marks,total_marks')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
