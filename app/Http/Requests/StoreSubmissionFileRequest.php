<?php

namespace App\Http\Requests;

use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Support\SubmissionFileRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Submission|null $submission */
        $submission = $this->route('submission');

        return $submission !== null && ($this->user()?->can('create', [SubmissionFile::class, $submission]) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $fileType = $this->string('file_type')->toString();
        $definition = SubmissionFileRegistry::exists($fileType)
            ? SubmissionFileRegistry::definition($fileType)
            : null;

        return [
            'file_type' => ['required', 'string', Rule::in(array_keys(SubmissionFileRegistry::definitions()))],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => array_filter([
                'required',
                'file',
                $definition !== null ? 'mimes:'.implode(',', $definition['extensions']) : null,
                $definition !== null ? 'max:'.$definition['maxKilobytes'] : null,
            ]),
        ];
    }
}
