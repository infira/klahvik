<?php

namespace Infira\Klahvik\config;

use Wolo\File\Path;

class Server extends Manager
{
	public function __construct(array $realConfig, string $parentInstance = '')
	{
		$configConfig = [
			'user'        => 'string',
			'host'        => 'string',
			'port'        => '??int',
			'klahvikPath' => 'stringPath',
			'tmpPath'     => '??stringPath:[klahvikPath]tmp',
		];
		parent::__construct('server', $parentInstance, $configConfig, $realConfig);
	}
	
	public function getUser(): string
	{
		return $this->get('user');
	}
	
	public function getHost(): string
	{
		return $this->get('host');
	}
	
	public function getPort(): ?int
	{
		return $this->get('port');
	}
	
	public function getKlahvikPath(string $path = ''): string
	{
		return Path::join($this->get('klahvikPath'), $path);
	}
	
	public function getTmpPath(string $path = ''): string
	{
		return Path::join($this->get('tmpPath'), $path);
	}
	
}