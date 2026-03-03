<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionAnswer extends Model
{
    protected $fillable = [
        'submission_id', 'question_id', 'option_id', 'is_correct',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
