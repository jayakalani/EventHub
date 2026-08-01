<?php

namespace App\Models\Concerns;

use App\Support\TitleCase;

trait HasTitleCaseAttributes
{
    /**
     * Attributes listed in $titleCase (or titleCaseAttributes()) are
     * automatically title-cased before the model is saved.
     */
    protected static function bootHasTitleCaseAttributes(): void
    {
        static::saving(function ($model): void {
            foreach ($model->titleCaseAttributes() as $attribute) {
                if (! array_key_exists($attribute, $model->getAttributes())) {
                    continue;
                }

                $value = $model->getAttributes()[$attribute] ?? null;

                if (! is_string($value) || $value === '') {
                    continue;
                }

                $model->setAttribute($attribute, TitleCase::format($value));
            }
        });
    }

    /**
     * @return list<string>
     */
    protected function titleCaseAttributes(): array
    {
        return property_exists($this, 'titleCase') && is_array($this->titleCase)
            ? array_values($this->titleCase)
            : [];
    }
}
