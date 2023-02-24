<?php

namespace Infira\Klahvik\config;

class ClientConfig extends ConfigCollection
{
    public function getDb(): DbConfig
    {
        $generalConfig = Config::getDb();
        $dbConfig = $this->getArray('db');
        $config = array_merge($generalConfig, $dbConfig);

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
        if (!$this->exists('rsync')) {
            return [];
        }
        $s = $this->get('rsync');

        return is_array($s) ? $s : [];
    }
}