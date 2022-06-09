<?php

namespace Infira\Klahvik\helper;

use Infira\Klahvik\config\Machine;
use Infira\Klahvik\console\Console;

class MachineInstance
{
	private string $name      = '';
	private string $sayPrefix = '';
	
	public function __construct(string $name, protected Machine $config)
	{
		$this->name = $name;
	}
	
	public function section(string $title, callable $between): void
	{
		$this->sayPrefix = $title;
		$between();
		$this->sayPrefix = trim(substr($this->sayPrefix, 0, strlen($title)));
	}
	
	public function say(string $msg = ''): static
	{
		Console::$name   = $this->name;
		Console::$prefix = $this->sayPrefix;
		$msg             = Console::into1Line($msg);
		if (!$msg) {
			return $this;
		}
		Console::say($msg);
		
		return $this;
	}
	
	public function tmp(string $path = ''): string
	{
		return $this->config->getTmpPath($path);
	}
	
	public function process(string $command): Process
	{
		$process = Process::fromShellCommandline($this->makeCommand($command));
		$process->setTimeout(1800);
		$process->setSpeaker(fn($line) => $this->say($line));
		
		return $process;
	}
	
	public function runBash(string $scriptPath, string $arguments = ''): void
	{
		$arguments = $arguments ?: " $arguments";
		$this->process($scriptPath . $arguments)->run(function ($line)
		{
			if (str_contains($line, 'error')) {
				Console::error($line);
			}
			$this->say($line);
		});
	}
	
	public function runKlahvikScript(string $script, string $arguments = ''): void
	{
		$this->runBash($this->config->getKlahvikPath("bash/$script"), $arguments);
	}
	
	protected function makeCommand(string $command): string { return $command; }
}