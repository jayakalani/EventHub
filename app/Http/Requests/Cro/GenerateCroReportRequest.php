<?php

namespace App\Http\Requests\Cro;

use App\Services\CroReports\CroReportRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateCroReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $catalog = app(CroReportRegistry::class)->catalogFor($this->user());
        $reportKeys = array_keys($catalog);

        return [
            'report' => ['required', 'string', Rule::in($reportKeys)],
            'format' => ['required', 'string', Rule::in(['pdf', 'csv'])],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string'],
            'filters' => ['nullable', 'array'],
            'filters.q' => ['nullable', 'string', 'max:255'],
            'filters.status' => ['nullable', 'string', 'max:50'],
            'filters.assignment' => ['nullable', 'string', Rule::in(['all', 'me', 'unassigned'])],
            'filters.event_id' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where('contact_person', $this->user()?->id),
            ],
            'filters.period' => ['nullable', 'string', Rule::in(['week', 'month', 'custom'])],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date', 'after_or_equal:filters.date_from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $reportKey = (string) $this->input('report');
            $definition = app(CroReportRegistry::class)->definitionFor($this->user(), $reportKey);

            if (! $definition) {
                $validator->errors()->add('report', 'Invalid report selection.');

                return;
            }

            $format = (string) $this->input('format');
            if (! in_array($format, $definition['formats'] ?? [], true)) {
                $validator->errors()->add('format', 'This format is not available for the selected report.');
            }

            if (! ($definition['skips_fields'] ?? false)) {
                $allowed = array_keys($definition['fields'] ?? []);
                $selected = array_values(array_intersect($this->input('fields', []), $allowed));

                if ($selected === []) {
                    $validator->errors()->add('fields', 'Select at least one field.');
                }
            }

            foreach ($definition['filters'] ?? [] as $filter) {
                if (! ($filter['required'] ?? false)) {
                    continue;
                }

                $showWhen = $filter['show_when'] ?? null;
                if (is_array($showWhen)) {
                    $visible = true;
                    foreach ($showWhen as $key => $expected) {
                        if ((string) $this->input('filters.'.$key) !== (string) $expected) {
                            $visible = false;
                            break;
                        }
                    }
                    if (! $visible) {
                        continue;
                    }
                }

                $value = $this->input('filters.'.$filter['key']);
                if ($value === null || $value === '') {
                    $validator->errors()->add(
                        'filters.'.$filter['key'],
                        $filter['label'].' is required.',
                    );
                }
            }
        });
    }

    /**
     * @return list<string>
     */
    public function selectedFields(): array
    {
        $reportKey = (string) $this->input('report');
        $definition = app(CroReportRegistry::class)->definitionFor($this->user(), $reportKey);
        $allowed = array_keys($definition['fields'] ?? []);

        return array_values(array_intersect($this->input('fields', []), $allowed));
    }

    /**
     * @return array<string, mixed>
     */
    public function selectedFilters(): array
    {
        $filters = $this->input('filters', []);

        return is_array($filters) ? $filters : [];
    }
}
