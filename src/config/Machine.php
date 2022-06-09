<?php

namespace Infira\Klahvik\config;

use Wolo\File\Path;

class Machine extends Manager
{
	public function __construct(string $instance, array $realConfig, string $parentInstance = '', array $keyConfig = [])
	{
		$configConfig = array_merge($keyConfig, [
			'klahvikPath' => 'stringPath',
			'tmpPath'     => '??stringPath:[klahvikPath]tmp',
		]);
		parent::__construct($instance, $parentInstance, $configConfig, $realConfig);
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