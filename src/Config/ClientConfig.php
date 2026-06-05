<?php

namespace Infira\Klahvik\Config;

use Infira\Console\Config as BaseConfig;
use Infira\Klahvik\Machine\Config\SshServerConfig;

class ClientConfig extends BaseConfig
{
    public function getDb(): DbConfig
    {
        return Klahvik::getDb()->extend(
            $this->getAs('db', DbConfig::class)
        );
    }

    public function getServerConfig(): SshServerConfig
    {
        return $this->getAs('server', SshServerConfig::class);
    }

    public function storm(): BaseConfig
    {
        return $this->getAs('storm', BaseConfig::class);
    }

    public function getPhpStorm(string $project): PhpStormConfig
    {
        return $this->storm()->getAs($project, PhpStormConfig::class);
    }

    public function getRSync(): array
    {
        return (array)$this->get('rsync', []);
    }
}