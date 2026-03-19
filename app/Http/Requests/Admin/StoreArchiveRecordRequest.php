<?php

namespace App\Http\Requests\Admin;

use App\ArchiveStatus;
use App\PublicVisibilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArchiveRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_id' => ['required', 'integer', 'exists:submissions,id'],
            'ranking_snapshot_id' => ['nullable', 'integer', 'exists:ranking_snapshots,id'],
            'competition_session_id' => ['nullable', 'integer', 'exists:competition_sessions,id'],
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
