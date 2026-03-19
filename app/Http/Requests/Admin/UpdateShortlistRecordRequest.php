<?php

namespace App\Http\Requests\Admin;

use App\Models\ShortlistRecord;
use App\ShortlistApprovalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShortlistRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shortlistRecord = $this->route('shortlistRecord');

        return $shortlistRecord instanceof ShortlistRecord && ($this->user()?->can('update', $shortlistRecord) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rank_order_optional' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'approval_status' => ['required', Rule::enum(ShortlistApprovalStatus::class)],
        ];
    }
}
