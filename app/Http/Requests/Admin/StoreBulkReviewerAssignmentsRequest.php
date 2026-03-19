<?php

namespace App\Http\Requests\Admin;

use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBulkReviewerAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ReviewerAssignment::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reviewer_ids' => ['required', 'array', 'min:1'],
            'reviewer_ids.*' => ['integer', Rule::exists(Reviewer::class, 'id')],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
