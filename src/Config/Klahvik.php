<?php

namespace Infira\Klahvik\Config;

use Infira\Console\Config;
use Infira\Klahvik\Machine\Config\DockerImageConfig;
use Infira\Klahvik\Machine\Config\LocalHostConfig;

class Klahvik
{
    private static Config $config;

    public static function setConfig(array $realConfig): void
    {
        self::$config = new Config($realConfig);
    }

    public static function getKlahvikPath(string ...$path): string
    {
        return self::$config->getPath('klahvikPath', ...$path);
    }

    public static function getComposer(): string
    {
        return self::$config->get('composer');
    }

    public static function getDb(): DbConfig
    {
        return self::$config->getAs('db', DbConfig::class);
    }

    public static function getDocker(): DockerImageConfig
    {
        return self::$config->getAs('docker', DockerImageConfig::class);
    }

    public static function getLocalhost(): LocalHostConfig
    {
        return self::$config->getAs('local', LocalHostConfig::class);
    }

    public static function getClient(string $client): ClientConfig
    {
        return self::$config->getAs('clients', ClientConfig::class)->getAs($client, ClientConfig::class);
    }

    /**
     * @return string[]
     */
    public static function getClientNames(): array
    {
        return array_keys(self::$config->get('clients'));
    }
}