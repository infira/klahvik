<?php

namespace Infira\Klahvik\config;

class Docker extends Machine
{
	public function __construct(array $realConfig, string $parentInstance = '')
	{
		$configConfig = [
			'image'    => 'string',
			'password' => 'string',
		];
		parent::__construct('docker', $realConfig, $parentInstance, $configConfig);
	}
	
	public function getImage(): string
	{
		return $this->get('image');
	}
	
	public function getRootPassword(): string
	{
		return $this->get('password');
	}
}