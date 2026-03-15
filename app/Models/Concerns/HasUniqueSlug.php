<?php

namespace App\Models\Concerns;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

trait HasUniqueSlug
{
    /**
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = []): bool
    {
        try {
            return parent::save($options);
        } catch (UniqueConstraintViolationException $e) {
            // Slug collision from race condition — retry with a random suffix on insert only
            if (! empty($this->slug) && ! $this->exists) {
                $this->slug .= '-' . Str::lower(Str::random(5));
                return parent::save($options);
            }
            throw $e;
        }
    }
}
