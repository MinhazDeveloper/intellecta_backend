<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'user_id', 'exam_id', 'total_questions', 'correct_answers',
        'wrong_answers', 'obtained_marks', 'time_taken', 'submitted_at',
    ];

    protected $casts = [
        'obtained_marks' => 'integer',
        'time_taken' => 'integer', // storing time in minutes or seconds
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // submission has many answer
    public function submissionAnswers()
    {
        return $this->hasMany(SubmissionAnswer::class);
    }
}
