<?php

namespace App\Http\Requests\Admin;

use App\Models\Judge;
use App\Models\Panel;
use App\Models\Rubric;
use App\Models\Season;
use App\Models\Stage;
use App\PanelRole;
use App\PanelStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Panel::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'season_id' => ['required', 'integer', Rule::exists(Season::class, 'id')],
            'stage_id' => ['required', 'integer', Rule::exists(Stage::class, 'id')],
            'rubric_id' => ['required', 'integer', Rule::exists(Rubric::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(PanelStatus::class)],
            'members' => ['array'],
            'members.*.judge_id' => ['required', 'integer', Rule::exists(Judge::class, 'id')],
            'members.*.role_in_panel' => ['required', Rule::enum(PanelRole::class)],
            'members.*.display_order' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $stage = Stage::query()->find($this->input('stage_id'));

            if ($stage !== null && (int) $stage->season_id !== (int) $this->input('season_id')) {
                $validator->errors()->add('stage_id', 'The selected stage must belong to the selected season.');
            }

            $members = collect($this->input('members', []));

            if ($members->pluck('judge_id')->filter()->duplicates()->isNotEmpty()) {
                $validator->errors()->add('members', 'The same judge cannot be added to a panel more than once.');
            }

            if ($members->isNotEmpty() && $members->where('role_in_panel', PanelRole::Chair->value)->count() !== 1) {
                $validator->errors()->add('members', 'Each panel must have exactly one chair.');
            }
        });
    }
}
