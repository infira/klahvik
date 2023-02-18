<?php

namespace Infira\Klahvik\config;

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

    public function projectExists(string $project): bool
    {
        return $project == 'all' || isset($this->getArray('projects')[$project]);
    }

    public function getRemoteName(string $project): string
    {
        $projects = $this->getArray('projects');
        if (!isset($projects[$project])) {
            $this->error('projects', "project('$project') does not exist");
        }

        return $projects[$project];
    }

    public function getVoidDataDumpTables(): array
    {
        $tables = $this->getArray('voidDataDumpTables');
        array_walk($tables, fn($table) => trim($table));

        return $tables;
    }

}