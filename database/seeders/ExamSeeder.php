<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exam = Exam::create([
            'title' => 'General Knowledge Quiz',
            'subject' => 'General',
            'total_marks' => 10,
            'pass_marks' => 5,
            'duration_minutes' => 10,
            'is_published' => 1,
            'user_id' => 2, // Instructor User ID
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $question = $exam->questions()->create([
                'question_text' => "Sample Question Number $i?",
                'user_id' => 2, // Instructor User ID
            ]);

            for ($j = 1; $j <= 4; $j++) {
                $question->options()->create([
                    'option_text' => "Option $j for question $i",
                    'is_correct' => ($j === 1)
                ]);
            }
        }
    }
}
