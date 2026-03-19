<?php

namespace App\Http\Requests;

use App\Models\ReviewerAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionReviewerAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('reviewerAssignment');

        return $assignment instanceof ReviewerAssignment && ($this->user()?->can('update', $assignment) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'intent' => ['required', Rule::in(['reopen'])],
            'reason' => ['required', 'string', 'max:10000'],
        ];
    }
}
