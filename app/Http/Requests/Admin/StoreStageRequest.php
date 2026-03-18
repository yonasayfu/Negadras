<?php

namespace App\Http\Requests\Admin;

use App\StageStatus;
use App\StageType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stages.create') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'season_id' => ['required', Rule::exists('seasons', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('stages', 'code')->where(fn ($query) => $query->where('season_id', $this->integer('season_id'))),
            ],
            'type' => ['required', Rule::enum(StageType::class)],
            'order_index' => ['required', 'integer', 'min:1', 'max:999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::enum(StageStatus::class)],
            'is_live_stage' => ['required', 'boolean'],
        ];
    }
}
