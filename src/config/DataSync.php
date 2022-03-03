<?php

namespace Infira\Klahvik\config;

use Wolo\File\Path;

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
	
	public function getSource(string $path = ''): string
	{
		return Path::join($this->get('src'), $path);
	}
	
	public function getDest(string $path = ''): string
	{
		return Path::join($this->get('dest'), $path);
	}
}