<?php

namespace Infira\Klahvik\config;

class Server extends Machine
{
	public function __construct(array $realConfig, string $parentInstance = '')
	{
		$configConfig = [
			'user' => 'string',
			'host' => 'string',
			'port' => '??int',
		];
		parent::__construct('server', $realConfig, $parentInstance, $configConfig);
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
}