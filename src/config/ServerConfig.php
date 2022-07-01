<?php

namespace Infira\Klahvik\config;

class ServerConfig extends MachineConfig
{
	public function getUser(): string
	{
		return $this->getString('user');
	}
	
	public function getHost(): string
	{
		return $this->getString('host');
	}
	
	public function getPort(): ?int
	{
		return $this->getReal('port');
	}
}