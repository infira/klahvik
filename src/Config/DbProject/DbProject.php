<?php

namespace Infira\Klahvik\Config\DbProject;

use Infira\Console\Config;
use Infira\FluentValue\Flu;

class DbProject extends Config implements \Stringable
{
    public function getRemoteDbName(): string
    {
        return $this->get('remote');
    }

    public function getLocalDbName(string $branch, string $project): string
    {
        return Flu::render(trim($this->get('local', '')), [
            'branch' => $branch,
            'project' => $project,
        ]);
    }

    public function getName(): string
    {
        return $this->get('name');
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}