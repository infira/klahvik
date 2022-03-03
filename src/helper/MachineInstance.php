<?php

namespace Infira\Klahvik\helper;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Infira\console\Console;

class MachineInstance
{
	private $name     = '';
	private $sayTitle = '';
	
	public function __construct(string $name)
	{
		$this->name = $name;
	}
	
	public function section(string $title, callable $between)
	{
		$this->sayTitle = $title;
		$between();
		$this->sayTitle = trim(substr($this->sayTitle, 0, strlen($title)));
	}
	
	public function say(string $msg = '')
	{
		$msg = Console::into1Line($msg);
		if (!$msg) {
			return $this;
		}
		$outputStyle = new OutputFormatterStyle('magenta');
		Console::$output->getFormatter()->setStyle('title', $outputStyle);
		
		$msg   = trim($msg);
		$msg   = $msg ? " $msg" : '';
		$title = $this->sayTitle ? "<title> $this->sayTitle </title>" : '';
		$msg   = "<fg=black;bg=bright-yellow>$this->name: </>$title$msg";
		Console::say($msg);
		
		return $this;
	}
}