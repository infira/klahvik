<?php

namespace Infira\Klahvik\Config;

use Illuminate\Support\Collection;
use Infira\Klahvik\Config\DbProject\DbProject;
use Infira\Klahvik\Config\DbProject\DbProjectCollection;
use Infira\Console\Config;

class DbConfig extends Config
{
    protected array $defaultConfig = [
        'projects' => [],
        'voidDataDumpTables' => [],
        'localNameTemplate' => 'local_{branch}_{project}',
    ];

    public function extend(array|Config $data): static
    {
        $current = $this->toArray();
        $data = $data instanceof static ? $data : new static($data);

        return new static([
            ...$current,
            ...$data->toArray(),
            'voidDataDumpTables' => $this->getVoidDataDumpTables()->merge($data->getVoidDataDumpTables())->unique()->all(),
            'localNameTemplate' => $data->get('localNameTemplate', $this->get('localNameTemplate', 'local_{branch}_{project}')),
        ]);
    }

    public function getHost(): ?string
    {
        return $this->get('host',null);
    }

    public function groupSuffix(): ?string
    {
        return $this->get('groupSuffix',null);
    }

    public function mysqlArguments(): ?array
    {
        return $this->get('mysqlArguments',null);
    }

    public function getUser(): ?string
    {
        return $this->get('user',null);
    }

    public function getPass(): ?string
    {
        return $this->get('pass',null);
    }

    public function projects(): DbProjectCollection
    {
        $projects = new DbProjectCollection();
        foreach ($this->get('projects') as $projectName => $projectConfig) {
            $finalConfig = [
                'name' => $projectName,
                'remote' => null,
                'local' => $this->get('localNameTemplate', 'local_{branch}_{project}'),
            ];
            if (is_string($projectConfig)) {
                $finalConfig['remote'] = $projectConfig;
            }
            else {
                $finalConfig = array_merge($finalConfig, $projectConfig);
            }
            $projects[$projectName] = new DbProject($finalConfig);
        }

        return $projects;
    }

    public function getVoidDataDumpTables(): Collection
    {
        return $this->getAsCollection('voidDataDumpTables')
            ->map(static fn($table) => trim($table));
    }
}