<?php

namespace Infira\Klahvik\config;

use Infira\Klahvik\Klahvik;

class DataSync extends Manager
{
	public function __construct(array $realConfig, string $parentInstance = '')
	{
		$configConfig = [
			'src'  => 'stringPath',
			'dest' => 'stringPath',
		];
		parent::__construct('sync', $parentInstance, $configConfig, $realConfig);
	}
	
	public function getSource(string $path = '')
	{
		return Klahvik::fixPath($this->get('src') . $path);
	}
	
	public function getDest(string $path = '')
	{
		return Klahvik::fixPath($this->get('dest') . $path);
	}
}