<?php

namespace App\Http\Requests;

use App\ConflictOfInterestType;
use App\Models\ConflictOfInterestDeclaration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConflictDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ConflictOfInterestDeclaration::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'conflict_type' => ['required', Rule::enum(ConflictOfInterestType::class)],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }
}
