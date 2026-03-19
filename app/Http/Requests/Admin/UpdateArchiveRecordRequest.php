<?php

namespace App\Http\Requests\Admin;

use App\ArchiveStatus;
use App\PublicVisibilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArchiveRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archive_status' => ['required', Rule::enum(ArchiveStatus::class)],
            'public_visibility' => ['required', Rule::enum(PublicVisibilityStatus::class)],
            'notes' => ['nullable', 'string'],
            'showcase_title' => ['nullable', 'string', 'max:255'],
            'showcase_subtitle' => ['nullable', 'string', 'max:255'],
            'showcase_summary' => ['nullable', 'string'],
            'showcase_winner_label' => ['nullable', 'string', 'max:255'],
            'showcase_media_id_optional' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }
}
