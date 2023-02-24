<?php

namespace Infira\Klahvik\config\Models;

use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use Infira\FluentValue\FluentValue;

/**
 * @property-read FluentValue $name
 * @property-read FluentValue $db
 */
class DbProject extends Repository
{
    public function __get(string $name)
    {
        return flu(parent::get($name));
    }

    public function __toString(): string
    {
        return $this->name->toString();
    }

    public function tasks(): Collection
    {
        return collect((array)$this->get('tasks', []));
    }
}