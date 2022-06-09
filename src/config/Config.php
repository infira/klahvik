<?php

namespace Infira\Klahvik\config;

use Wolo\File\Path;

class Config
{
	private static MainConfig $config;
	
	public static function set(array $realConfig): void
	{
		self::$config = new MainConfig($realConfig);
	}
	
	public static function getLocalTmpPath(string $path = ''): string
	{
		return Path::join(self::$config->get('localTmpPath'), $path);
	}
	
	public static function getDb(): Db
	{
		return self::$config->getDb();
	}
	
	public static function getVagrant(): Server
	{
		return self::$config->getVagrant();
	}
	
	public static function getDocker(): Docker
	{
		return self::$config->getDocker();
	}
	
	public static function getLocal(): LocalConfig
	{
		return self::$config->getLocal();
	}
	
	public static function getClient(string $client): Client
	{
		return self::$config->getClient($client);
	}
	
	public static function getClients(): array
	{
		return self::$config->getClients();
	}
}