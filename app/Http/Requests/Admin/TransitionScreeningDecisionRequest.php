<?php

namespace App\Http\Requests\Admin;

use App\Models\Submission;
use App\SubmissionStatus;
use App\Support\SubmissionStatusTransitionService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionScreeningDecisionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Submission $submission */
        $submission = $this->route('submission');

        return $this->user()?->can('update', $submission) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Submission $submission */
        $submission = $this->route('submission');
        $service = app(SubmissionStatusTransitionService::class);
        $allowedTransitions = collect($service->availableScreeningDecisionTransitions($submission))
            ->pluck('value')
            ->all();

        return [
            'status' => ['required', 'string', Rule::in($allowedTransitions)],
            'reason' => [
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $requiresReason = in_array($this->input('status'), [
                        SubmissionStatus::IncompleteReturned->value,
                        SubmissionStatus::Rejected->value,
                    ], true);

                    if (blank($value)) {
                        if ($requiresReason) {
                            $fail('A reason is required for this screening decision.');
                        }

                        return;
                    }

                    if (! is_string($value)) {
                        $fail('The reason must be a string.');
                    }

                    if (is_string($value) && mb_strlen($value) > 2000) {
                        $fail('The reason may not be greater than 2000 characters.');
                    }
                },
            ],
        ];
    }
}
