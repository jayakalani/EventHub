<?php

namespace App\Services\OrganizerReports;

use App\Models\Event;
use App\Models\User;
use App\Services\OrganizerReports\Contracts\ReportGenerator;
use InvalidArgumentException;

class OrganizerReportRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function catalogFor(User $user): array
    {
        $catalog = [];

        foreach (config('organizer_reports.reports', []) as $key => $definition) {
            $catalog[$key] = $this->resolveDefinition($key, $definition, $user);
        }

        return $catalog;
    }

    /**
     * Catalog grouped in dashboard tab order for the report builder UI.
     *
     * @return list<array{key: string, label: string, icon: string, description: string, reports: list<array<string, mixed>>}>
     */
    public function groupedCatalogFor(User $user): array
    {
        $catalog = $this->catalogFor($user);
        $groupsConfig = config('organizer_reports.groups', []);
        $grouped = [];

        foreach ($groupsConfig as $groupKey => $meta) {
            $reports = array_values(array_filter(
                $catalog,
                fn (array $report) => ($report['group'] ?? '') === $groupKey
            ));

            if ($reports === []) {
                continue;
            }

            $grouped[] = [
                'key' => $groupKey,
                'label' => (string) ($meta['label'] ?? ucfirst($groupKey)),
                'icon' => (string) ($meta['icon'] ?? 'bi-file-earmark-text'),
                'description' => (string) ($meta['description'] ?? ''),
                'reports' => $reports,
            ];
        }

        $knownGroups = array_keys($groupsConfig);
        $ungrouped = array_values(array_filter(
            $catalog,
            fn (array $report) => ! in_array($report['group'] ?? '', $knownGroups, true)
        ));

        if ($ungrouped !== []) {
            $grouped[] = [
                'key' => 'other',
                'label' => 'Other',
                'icon' => 'bi-file-earmark-text',
                'description' => 'Additional exports',
                'reports' => $ungrouped,
            ];
        }

        return $grouped;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function definitionFor(User $user, string $key): ?array
    {
        return $this->catalogFor($user)[$key] ?? null;
    }

    public function generator(string $key): ReportGenerator
    {
        $class = config('organizer_reports.generators.'.$key);

        if (! is_string($class) || ! class_exists($class)) {
            throw new InvalidArgumentException("Unknown organizer report generator [{$key}].");
        }

        $generator = app($class);

        if (! $generator instanceof ReportGenerator) {
            throw new InvalidArgumentException("Organizer report generator [{$key}] must implement ReportGenerator.");
        }

        return $generator;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function resolveDefinition(string $key, array $definition, User $user): array
    {
        $kind = (string) ($definition['kind'] ?? 'tabular');
        $isAnalytics = $kind === 'analytics';

        $fields = [];
        foreach ($definition['fields'] ?? [] as $fieldKey => $label) {
            $fields[$fieldKey] = (string) $label;
        }

        $filters = [];
        foreach ($definition['filters'] ?? [] as $filter) {
            $type = (string) ($filter['type'] ?? 'text');
            $resolved = [
                'key' => $filter['key'],
                'type' => $type,
                'label' => (string) $filter['label'],
                'options' => [],
                'show_when' => $filter['show_when'] ?? null,
                'required' => (bool) ($filter['required'] ?? false),
            ];

            if ($type === 'enum' && isset($filter['enum']) && enum_exists($filter['enum'])) {
                $resolved['type'] = 'select';
                foreach ($filter['enum']::cases() as $case) {
                    $resolved['options'][$case->value] = method_exists($case, 'label')
                        ? $case->label()
                        : (string) $case->value;
                }
            } elseif ($type === 'select' && isset($filter['options'])) {
                foreach ($filter['options'] as $optKey => $optLabel) {
                    $resolved['options'][$optKey] = (string) $optLabel;
                }
            } elseif ($type === 'events') {
                $resolved['type'] = 'select';
                $resolved['options'] = $this->eventOptions((int) $user->id);
            }

            $filters[] = $resolved;
        }

        return [
            'key' => $key,
            'label' => (string) $definition['label'],
            'description' => (string) ($definition['description'] ?? ''),
            'group' => (string) ($definition['group'] ?? 'other'),
            'formats' => $definition['formats'] ?? ['pdf'],
            'fields' => $fields,
            'filters' => $filters,
            'kind' => $kind,
            'is_analytics' => $isAnalytics,
            'skips_fields' => $isAnalytics || $fields === [],
        ];
    }

    /**
     * @return array<int|string, string>
     */
    private function eventOptions(int $organizerId): array
    {
        return Event::query()
            ->createdByOrganizer($organizerId)
            ->orderByDesc('date')
            ->limit(100)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Event $event) => [$event->id => $event->name])
            ->all();
    }
}
