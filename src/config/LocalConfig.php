<?php

namespace Infira\Klahvik\config;

use Infira\Klahvik\console\Console;

class LocalConfig extends Machine
{
	public function __construct(array $realConfig, string $parentInstance = '')
	{
		parent::__construct('local', $realConfig, $parentInstance);
	}
	
	public function getKlahvikPath(string $path = ''): string
	{
		Console::error('cant use');
	}
}