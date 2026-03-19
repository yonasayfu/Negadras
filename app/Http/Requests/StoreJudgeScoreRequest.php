<?php

namespace App\Http\Requests;

use App\Models\ScoreEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreJudgeScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ScoreEntry::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'intent' => ['required', Rule::in(['draft', 'submit'])],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.criterion_id' => ['required', 'integer'],
            'scores.*.score_value' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.comment' => ['nullable', 'string', 'max:2000'],
            'private_comment' => ['nullable', 'string', 'max:5000'],
            'presenter_comment' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->string('intent')->toString() !== 'submit') {
                return;
            }

            $missingScores = collect($this->input('scores', []))
                ->contains(fn (array $score): bool => blank($score['score_value'] ?? null));

            if ($missingScores) {
                $validator->errors()->add('scores', 'All rubric criteria must be scored before submission.');
            }
        });
    }
}
