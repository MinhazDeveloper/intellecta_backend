<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['exam_id', 'user_id', 'question_text', 'image', 'mark', 'explanation'];

    protected $casts = [
        'mark' => 'integer',
    ];

    public function exam()
    {

        return $this->belongsTo(Exam::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }
}
