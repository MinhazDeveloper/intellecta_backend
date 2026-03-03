<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function index()
    {
        $instructorId = auth()->id();

        $questions = Question::with(['options', 'exam'])
            ->whereHas('exam', function ($query) use ($instructorId) {
                $query->where('user_id', $instructorId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json(['success' => true, 'data' => $questions]);
    }

    public function store(StoreQuestionRequest $request)
    {
        try {
            // DB Transaction
            return DB::transaction(function () use ($request) {

                $question = Question::create([
                    'exam_id' => $request->exam_id,
                    'user_id' => auth()->id(),
                    'question_text' => $request->question_text,
                    'mark' => $request->mark ?? 1,
                    'explanation' => $request->explanation,
                ]);

                // Bulk Insert Options for performance
                $optionsData = collect($request->options)->map(function ($opt) use ($question) {
                    return [
                        'question_id' => $question->id,
                        'option_text' => $opt['text'],
                        'is_correct'  => $opt['is_correct'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                })->toArray();

                Option::insert($optionsData);

                return response()->json([
                    'message' => 'Question and options created successfully!',
                    'data' => $question->load('options'),
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: '.$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $question = Question::findOrFail($id);
        $question->delete();

        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }

    public function update(StoreQuestionRequest $request, $id)
    {
        $question = Question::find($id);

        if (! $question) {
            return response()->json(['success' => false, 'message' => 'Question not found'], 404);
        }

        try {
            return DB::transaction(function () use ($request, $question) {

                $question->update([
                    'exam_id' => $request->exam_id,
                    'question_text' => $request->question_text,
                    'mark' => $request->mark,
                    'explanation' => $request->explanation,
                ]);

                // Delete old options and Bulk Insert new ones
                $question->options()->delete();

                $optionsData = collect($request->options)->map(function ($opt) use ($question) {
                    return [
                        'question_id' => $question->id,
                        'option_text' => $opt['text'],
                        'is_correct'  => $opt['is_correct'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                })->toArray();

                Option::insert($optionsData);

                return response()->json([
                    'success' => true,
                    'message' => 'Question and Options updated successfully!',
                    'data' => $question->load('options'),
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // get question for student
    public function getQuestionsForStudent($examId)
    {
        $exam = Exam::select('id', 'duration_minutes')->find($examId);

        if (! $exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        // Randomize questions for students to prevent cheating
        $questions = Question::with(['options' => function ($query) {
            $query->select('id', 'question_id', 'option_text');
        }])
        ->where('exam_id', $examId)
        ->inRandomOrder()
        ->get();

        return response()->json([
            'success' => true,
            'duration_minutes' => $exam->duration_minutes,
            'data' => $questions,
        ]);
    }
}
