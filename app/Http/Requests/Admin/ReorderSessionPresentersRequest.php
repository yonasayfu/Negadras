<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderSessionPresentersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('live-operations.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'presenters' => ['required', 'array', 'min:1'],
            'presenters.*.id' => ['required', 'integer', 'exists:session_presenters,id'],
            'presenters.*.order_index' => ['required', 'integer', 'min:1'],
        ];
    }
}
