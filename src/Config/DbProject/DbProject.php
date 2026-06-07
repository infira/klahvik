<?php

namespace Infira\Klahvik\Config\DbProject;

use Infira\Console\Config;
use Infira\Klahvik\helper\Utils;

class DbProject extends Config implements \Stringable
{
    public function getRemoteDbName(): string
    {
        return $this->get('remote');
    }

    public function getLocalDbName(string $branch, string $project): string
    {
        return Utils::renderString(trim($this->get('local', '')), [
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