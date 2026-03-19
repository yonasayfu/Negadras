<?php

namespace App\Http\Requests\Admin;

use App\Models\ReviewerAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReassignReviewerAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var ReviewerAssignment $reviewerAssignment */
        $reviewerAssignment = $this->route('reviewerAssignment');

        return $this->user()?->can('update', $reviewerAssignment) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reviewer_id' => ['required', 'integer', Rule::exists('reviewers', 'id')],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
