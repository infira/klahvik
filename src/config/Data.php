<?php

namespace Infira\Klahvik\config;

class Data extends Manager
{
	public function __construct(array $realConfig, string $parentInstance = '')
	{
		$configConfig = [
			'sync' => 'array',
		];
		parent::__construct('data', $parentInstance, $configConfig, $realConfig);
	}
	
	public function getSync(): array
	{
		$s = $this->get('sync');
		
		return is_array($s) ? $s : [];
	}
}