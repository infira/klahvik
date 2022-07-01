<?php

namespace Infira\Klahvik\config;

class DockerConfig extends MachineConfig
{
	public function getImage(): string
	{
		return $this->getString('image');
	}
	
	public function getRootPassword(): string
	{
		return $this->getString('password');
	}
}