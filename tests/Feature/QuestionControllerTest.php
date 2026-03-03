<?php

use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Option;
use Laravel\Sanctum\Sanctum;

/**
 * ১. টেস্ট: অথেন্টিকেশন ছাড়া স্টুডেন্ট এক্সাম দেখতে পারবে না
 */
it('prevents unauthenticated students from fetching questions', function () {
    $exam = Exam::factory()->create();

    $response = $this->getJson("/api/student/exams/{$exam->id}");

    $response->assertStatus(401); // Unauthorized
});

/**
 * ২. টেস্ট: স্টুডেন্ট সঠিক এক্সাম ডাটা এবং অপশন পাচ্ছে কি না
 */
it('allows authenticated students to fetch exam questions with options', function () {
    // একজন স্টুডেন্ট তৈরি করা
    $student = User::factory()->create(['role' => 'student']);
    
    // একটি এক্সাম এবং তার সাথে প্রশ্ন ও অপশন তৈরি
    $exam = Exam::factory()->create(['duration_minutes' => 30]);
    $question = Question::factory()->create(['exam_id' => $exam->id]);
    Option::factory()->count(4)->create(['question_id' => $question->id]);

    // Sanctum দিয়ে লগইন করানো
    Sanctum::actingAs($student);

    $response = $this->getJson("/api/student/exams/{$exam->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'question_text',
                    'options' => [
                        '*' => ['id', 'option_text']
                    ]
                ]
            ],
            'duration_minutes'
        ]);
});

/**
 * ৩. টেস্ট: এক্সাম সাবমিশন কাজ করছে কি না
 */
it('can submit an exam successfully', function () {
    $student = User::factory()->create(['role' => 'student']);
    $exam = Exam::factory()->create();
    $question = Question::factory()->create(['exam_id' => $exam->id]);
    $option = Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);

    Sanctum::actingAs($student);

    $payload = [
        'exam_id' => $exam->id,
        'answers' => [
            [
                'question_id' => $question->id,
                'selected_option_id' => $option->id
            ]
        ]
    ];

    $response = $this->postJson('/api/student/exam/submit', $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    // ডাটাবেসে সাবমিশন সেভ হয়েছে কি না চেক করা
    $this->assertDatabaseHas('submissions', [
        'user_id' => $student->id,
        'exam_id' => $exam->id,
        'obtained_marks' => 1 // যেহেতু উত্তর সঠিক ছিল
    ]);
});

/**
 * ৪. টেস্ট: ভুল বা ইনভ্যালিড ডাটা সাবমিট করলে এরর দেয় কি না
 */
it('returns validation errors for invalid submission payload', function () {
    $student = User::factory()->create(['role' => 'student']);
    Sanctum::actingAs($student);

    // খালি পেলোড পাঠানো
    $response = $this->postJson('/api/student/exam/submit', []);

    $response->assertStatus(422); // Validation Error
});