<?php

namespace App\Traits;

trait HasTranslations
{
    protected function translate(string $field)
    {
        $locale = app()->getLocale(); // en or ar

        $fieldLocale = "{$field}_{$locale}";

        if ($this->{$fieldLocale}) {
            return $this->{$fieldLocale};
        }

        // fallback to English
        return $this->{$field . '_en'} ?? null;
    }
}
