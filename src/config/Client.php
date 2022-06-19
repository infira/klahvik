<?php

namespace Infira\Klahvik\config;

class Client extends Manager
{
	private string $name;
	
	public function __construct(array $realConfig, string $name, string $parentInstance = '')
	{
		$this->name   = $name;
		$configConfig = [
			'db'     => '\\Infira\Klahvik\config\Db',
			'server' => '\\Infira\Klahvik\config\Server',
			'phps'   => '??\\Infira\Klahvik\config\PhpStorm',
		];
		parent::__construct('client', $parentInstance, $configConfig, $realConfig);
	}
	
	public function getName(): string
	{
		return $this->name;
	}
	
	public function getDb(): Db
	{
		return $this->get('db');
	}
	
	public function getServer(): Server
	{
		return $this->get('server');
	}
	
	public function getPhpStorm(): ?PhpStorm
	{
		return $this->get('phps');
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