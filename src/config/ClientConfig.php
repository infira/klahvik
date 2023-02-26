<?php

namespace Infira\Klahvik\config;

class ClientConfig extends ConfigCollection
{
    public function getDb(): DbConfig
    {
        $config = array_merge(Config::getDb(), $this->get('db', []));

        return new DbConfig($config);
    }

    public function getServer(): ServerConfig
    {
        return $this->collection('server', ServerConfig::class);
    }

    public function storm(): ConfigCollection
    {
        return $this->collection('storm');
    }

    public function getPhpStorm(string $project): PhpStormConfig
    {
        return $this->storm()->collection($project, PhpStormConfig::class);
    }

    public function getRSync(): array
    {
        if (!$this->has('rsync')) {
            return [];
        }
        $s = $this->get('rsync');

        return is_array($s) ? $s : [];
    }
}