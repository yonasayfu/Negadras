<?php

namespace App\Http\Requests\Admin;

use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePanelSubmissionAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('panel')) ?? false;
    }

    public function rules(): array
    {
        return [
            'submission_id' => ['required', 'integer', Rule::exists(Submission::class, 'id')],
        ];
    }
}
