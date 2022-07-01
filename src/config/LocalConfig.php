<?php

namespace Infira\Klahvik\config;

use Infira\Klahvik\console\Console;

class LocalConfig extends MachineConfig
{
	public function getKlahvikPath(string $path = ''): string
	{
		Console::error('cant use');
	}
}