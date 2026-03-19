<?php

namespace App\Http\Requests\Admin;

use App\FeedbackVisibilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePresenterFeedbackPacketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'summary' => ['required', 'string'],
            'strengths' => ['nullable', 'string'],
            'improvement_areas' => ['nullable', 'string'],
            'next_step_guidance' => ['nullable', 'string'],
            'visibility_status' => ['required', Rule::enum(FeedbackVisibilityStatus::class)],
        ];
    }
}
