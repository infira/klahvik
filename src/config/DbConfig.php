<?php

namespace Infira\Klahvik\config;

use Illuminate\Config\Repository;
use Infira\Klahvik\config\Models\DbProject;

class DbConfig extends ConfigCollection
{
    public function getLocalName(string $branch, string $project): string
    {
        $name = $this->getString('localNameTemplate');
        $name = str_replace('{branch}', $branch, $name);
        $name = str_replace('{project}', $project, $name);

        return trim($name);
    }

    public function getHost(): ?string
    {
        if (!$this->exists('host')) {
            return null;
        }

        return $this->getString('host');
    }

    public function groupSuffix(): ?string
    {
        if (!$this->exists('groupSuffix')) {
            return null;
        }

        return $this->getString('groupSuffix');
    }

    public function getUser(): ?string
    {
        if (!$this->exists('user')) {
            return null;
        }

        return $this->getString('user');
    }

    public function getPass(): ?string
    {
        if (!$this->exists('pass')) {
            return null;
        }

        return $this->getString('pass');
    }

    public function getProjectNames(): array
    {
        return array_keys($this->getArray('projects'));
    }

    public function project(string $project): DbProject
    {
        $projects = new Repository($this->getArray('projects'));
        if (!$projects->has($project)) {
            $this->error('projects', "project('$project') does not exist");
        }

        $arr = [
            'name' => $project,
            'db' => null,
            'tasks' => []
        ];
        if (is_string($projects[$project])) {
            $arr ['db'] = $projects[$project];
        }
        else {
            $arr = array_merge($arr, $projects[$project]);
        }

        return new DbProject($arr);
    }

    public function getVoidDataDumpTables(): array
    {
        $tables = $this->getArray('voidDataDumpTables');
        array_walk($tables, static fn($table) => trim($table));

        return $tables;
    }

}