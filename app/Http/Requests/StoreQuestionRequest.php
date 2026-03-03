<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_id' => 'required|exists:exams,id',
            'question_text' => 'required|string',
            'mark' => 'required|integer',
            'options' => 'required|array|min:2', // Minimum 2 options
            'options.*.text' => 'required|string',
            'options.*.is_correct' => 'required|boolean',

        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $options = $this->input('options', []);

            $correctCount = collect($options)
                ->where('is_correct', true)
                ->count();

            if ($correctCount < 1) {
                $validator->errors()->add(
                    'options',
                    'At least one option must be marked as correct.'
                );
            }
        });
    }
}
