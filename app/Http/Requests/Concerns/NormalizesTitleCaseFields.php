<?php

namespace App\Http\Requests\Concerns;

use App\Support\TitleCase;

trait NormalizesTitleCaseFields
{
    /**
     * Fields listed in titleCaseFields() are title-cased before validation.
     *
     * @return list<string>
     */
    protected function titleCaseFields(): array
    {
        return property_exists($this, 'titleCase') && is_array($this->titleCase)
            ? array_values($this->titleCase)
            : [];
    }

    protected function prepareForValidation(): void
    {
        $fields = $this->titleCaseFields();

        if ($fields === []) {
            return;
        }

        $payload = [];

        foreach ($fields as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            if (is_string($value)) {
                $payload[$field] = TitleCase::format($value);
            }
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
