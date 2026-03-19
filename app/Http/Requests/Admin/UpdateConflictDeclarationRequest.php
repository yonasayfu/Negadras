<?php

namespace App\Http\Requests\Admin;

use App\ConflictOfInterestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConflictDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('conflictDeclaration')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ConflictOfInterestStatus::class)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
