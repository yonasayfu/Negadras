<?php

namespace App\Http\Requests\Admin;

use App\FeedbackVisibilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePresenterFeedbackPacketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_id' => ['required', 'integer', 'exists:submissions,id'],
            'summary' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'improvement_areas' => ['nullable', 'string'],
            'next_step_guidance' => ['nullable', 'string'],
            'visibility_status' => ['nullable', Rule::enum(FeedbackVisibilityStatus::class)],
            'send_now' => ['nullable', 'boolean'],
        ];
    }
}
