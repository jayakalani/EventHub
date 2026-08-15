<?php

namespace App\Services\AdminReports;

use App\Models\Event;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AdminReports\Contracts\ReportGenerator;
use InvalidArgumentException;

class AdminReportRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function catalogFor(User $user): array
    {
        $catalog = [];

        foreach (config('admin_reports.reports', []) as $key => $definition) {
            $catalog[$key] = $this->resolveDefinition($key, $definition);
        }

        return $catalog;
    }

    /**
     * Catalog grouped for the export builder UI.
     *
     * @return list<array{key: string, label: string, icon: string, description: string, reports: list<array<string, mixed>>}>
     */
    public function groupedCatalogFor(User $user): array
    {
        $catalog = $this->catalogFor($user);
        $groupsConfig = config('admin_reports.groups', []);
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
        $class = config('admin_reports.generators.'.$key);

        if (! is_string($class) || ! class_exists($class)) {
            throw new InvalidArgumentException("Unknown admin report generator [{$key}].");
        }

        $generator = app($class);

        if (! $generator instanceof ReportGenerator) {
            throw new InvalidArgumentException("Admin report generator [{$key}] must implement ReportGenerator.");
        }

        return $generator;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function resolveDefinition(string $key, array $definition): array
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
                'hide_when' => $filter['hide_when'] ?? null,
                'required' => (bool) ($filter['required'] ?? false),
                'include_empty' => $filter['include_empty'] ?? true,
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
                $events = $this->filterEvents();
                $resolved['options'] = $events
                    ->mapWithKeys(fn (Event $event) => [$event->id => $event->filterLabel()])
                    ->all();
                $resolved['option_scopes'] = $events
                    ->mapWithKeys(fn (Event $event) => [$event->id => (int) $event->created_by])
                    ->all();
                $resolved['option_scope_maps'] = [
                    'organizer_id' => $events
                        ->mapWithKeys(fn (Event $event) => [$event->id => (int) $event->created_by])
                        ->all(),
                    'cro_id' => $events
                        ->filter(fn (Event $event) => filled($event->contact_person))
                        ->mapWithKeys(fn (Event $event) => [$event->id => (int) $event->contact_person])
                        ->all(),
                ];
                $resolved['scope_by'] = 'organizer_id';
                $resolved['scope_by_when'] = [
                    ['when' => ['section' => 'support'], 'scope_by' => 'cro_id'],
                ];
            } elseif ($type === 'organizers') {
                $resolved['type'] = 'select';
                $resolved['options'] = $this->organizerOptions();
            } elseif ($type === 'roles') {
                $resolved['type'] = 'select';
                $resolved['options'] = $this->roleOptions();
            } elseif ($type === 'cros') {
                $resolved['type'] = 'select';
                $resolved['options'] = $this->croOptions();
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
     * @return \Illuminate\Support\Collection<int, Event>
     */
    private function filterEvents()
    {
        return Event::query()
            ->forFilter()
            ->orderByDesc('date')
            ->get(['id', 'name', 'deleted_at', 'created_by', 'contact_person']);
    }

    /**
     * @return array<int|string, string>
     */
    private function organizerOptions(): array
    {
        $roleId = UserRole::query()->where('name_en', UserRole::ORGANIZER)->value('id');

        if (! $roleId) {
            return [];
        }

        return User::query()
            ->where('role_id', $roleId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(200)
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->mapWithKeys(fn (User $user) => [
                $user->id => trim($user->full_name).' ('.$user->email.')',
            ])
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    private function roleOptions(): array
    {
        return UserRole::query()
            ->orderBy('id')
            ->get(['id', 'name_en'])
            ->mapWithKeys(fn (UserRole $role) => [$role->id => $role->name_en])
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    private function croOptions(): array
    {
        $roleId = UserRole::query()->where('name_en', UserRole::CRO)->value('id');

        if (! $roleId) {
            return [];
        }

        return User::query()
            ->where('role_id', $roleId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user) => [$user->id => $user->full_name])
            ->all();
    }
}
