<?php

namespace App\Http\Requests\Admin;

use App\Services\AdminReports\AdminReportRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateAdminReportRequest extends FormRequest
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
        $catalog = app(AdminReportRegistry::class)->catalogFor($this->user());
        $reportKeys = array_keys($catalog);

        return [
            'report' => ['required', 'string', Rule::in($reportKeys)],
            'format' => ['required', 'string', Rule::in(['pdf', 'csv'])],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string'],
            'filters' => ['nullable', 'array'],
            'filters.q' => ['nullable', 'string', 'max:255'],
            'filters.status' => ['nullable', 'string', 'max:50'],
            'filters.state' => ['nullable', 'string', Rule::in(['active', 'inactive', 'locked'])],
            'filters.ticket_type' => ['nullable', 'string', Rule::in(['inquiry', 'complaint'])],
            'filters.check_in' => ['nullable', 'string', Rule::in(['checked_in', 'not_checked_in'])],
            'filters.active' => ['nullable', 'string', Rule::in(['0', '1'])],
            'filters.section' => ['nullable', 'string', Rule::in([
                'all',
                'full',
                'performance',
                'support',
                'overview',
                'activity',
                'events',
                'users',
                'payments',
                'admin',
            ])],
            'filters.role_id' => ['nullable', 'integer', 'exists:user_roles,id'],
            'filters.organizer_id' => ['nullable', 'integer', 'exists:users,id'],
            'filters.event_id' => ['nullable', 'integer', 'exists:events,id'],
            'filters.cro_id' => ['nullable', 'integer', 'exists:users,id'],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date', 'after_or_equal:filters.date_from'],
            'charts' => ['nullable', 'array', 'max:40'],
            'charts.*.title' => ['required', 'string', 'max:120'],
            'charts.*.image' => ['required', 'string', 'max:5000000'],
            'charts.*.section' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $reportKey = (string) $this->input('report');
            $definition = app(AdminReportRegistry::class)->definitionFor($this->user(), $reportKey);

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
        $definition = app(AdminReportRegistry::class)->definitionFor($this->user(), $reportKey);
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

    /**
     * @return list<array{title: string, image: string, section: string}>
     */
    public function selectedCharts(): array
    {
        return collect($this->validated()['charts'] ?? [])
            ->filter(function (array $chart) {
                $image = $chart['image'] ?? '';

                return is_string($image)
                    && preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $image) === 1;
            })
            ->map(fn (array $chart) => [
                'title' => (string) $chart['title'],
                'image' => (string) $chart['image'],
                'section' => (string) ($chart['section'] ?? ''),
            ])
            ->values()
            ->all();
    }
}
