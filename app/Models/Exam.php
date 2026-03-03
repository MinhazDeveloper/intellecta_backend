<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'title', 'subject', 'total_marks',
        'pass_marks', 'duration_minutes',
        'is_published', 'user_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'total_marks' => 'integer',
        'pass_marks' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
