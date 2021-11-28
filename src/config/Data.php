<?php

namespace Infira\Klahvik\config;

class Data extends Manager
{
	public function __construct(array $realConfig, string $parentInstance = '')
	{
		$configConfig = [
			'sync' => '??\\Infira\Klahvik\config\DataSync',
		];
		parent::__construct('data', $parentInstance, $configConfig, $realConfig);
	}
	
	public function getSync(): ?DataSync
	{
		return $this->get('sync');
	}
}