<?php

namespace Infira\Klahvik\config;

use Wolo\File\Path;

class Config
{
    private static ConfigCollection $config;

    public static function set(array $realConfig): void
    {
        self::$config = new ConfigCollection($realConfig);
    }

    public static function getLocalTmpPath(string $path = ''): string
    {
        return Path::join(self::$config->getPath('localTmpPath'), $path);
    }

    public static function getComposer(): string
    {
        return self::$config->getString('composer');
    }

    public static function getDb(): array
    {
        return self::$config->getArray('db');
    }

    public static function getVagrant(): ServerConfig
    {
        return self::$config->collection('vagrant', ServerConfig::class);
    }

    public static function getDocker(): DockerConfig
    {
        return self::$config->collection('docker', DockerConfig::class);
    }

    public static function getLocal(): LocalConfig
    {
        return self::$config->collection('local', LocalConfig::class);
    }

    public static function getClient(string $client): ClientConfig
    {
        return self::$config->collection('clients')->collection($client, ClientConfig::class);
    }

    /**
     * @return string[]
     */
    public static function getClientNames(): array
    {
        return array_keys(self::$config->getArray('clients'));
    }
}