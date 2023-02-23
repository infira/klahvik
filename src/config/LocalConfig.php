<?php

namespace Infira\Klahvik\config;

use Infira\Console\Console;

class LocalConfig extends MachineConfig
{
	public function getKlahvikPath(string $path = ''): string
	{
		Console::error('cant use');
	}
}