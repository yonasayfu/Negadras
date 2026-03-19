<?php

namespace App\Http\Requests\Admin;

use App\Models\ShortlistRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreShortlistRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ShortlistRecord::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rank_order_optional' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
