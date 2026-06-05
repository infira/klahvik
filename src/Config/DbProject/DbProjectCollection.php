<?php

namespace Infira\Klahvik\Config\DbProject;

use Illuminate\Support\Collection;

/**
 * @method DbProject get($key, $default = null)
 */
class DbProjectCollection extends Collection
{
    public function names(): array
    {
        return $this->keys()->toArray();
    }
}