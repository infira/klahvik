<?php

namespace Infira\Klahvik\console;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class Console extends \Infira\console\Console
{
	public static string $name   = '';
	public static string $prefix = '';
	
	public static function say(string $message = ''): void
	{
		$message = parent::into1Line($message);
		if (!$message) {
			return;
		}
		$outputStyle = new OutputFormatterStyle('magenta');
		parent::$output->getFormatter()->setStyle('prefix', $outputStyle);
		
		$message      = trim($message);
		$message      = $message ? " $message" : '';
		$title        = self::$prefix ? "<prefix> " . self::$prefix . " </prefix>" : '';
		$message      = "<fg=black;bg=bright-yellow>" . self::$name . ": </>$title$message";
		self::$name   = '';
		self::$prefix = '';
		parent::say($message);
	}
}