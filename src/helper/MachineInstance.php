<?php

namespace Infira\Klahvik\helper;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Infira\Klahvik\console\Command;
use Symfony\Component\Process\Process;

class MachineInstance
{
	private           $name     = '';
	private           $sayTitle = '';
	protected Command $cmd;
	
	public function __construct(string $name, Command &$cmd)
	{
		$this->name = $name;
		$this->cmd  = &$cmd;
	}
	
	public final function rsync(string $server, string $src, string $destination)
	{
		$process = Process::fromShellCommandline("rsync --timeout=360 -av --progress --del $server:$src $destination");
		$process->run(fn($type, $line) => $this->say($line));
	}
	
	public function title(string $title, callable $between)
	{
		$this->sayTitle = $title;
		$between();
		$this->sayTitle = trim(substr($this->sayTitle, 0, strlen($title)));
	}
	
	public function say(string $msg = '')
	{
		$msg = $this->cmd->output->into1Line($msg);
		if (!$msg)
		{
			return $this;
		}
		$outputStyle = new OutputFormatterStyle('magenta');
		$this->cmd->output->getFormatter()->setStyle('title', $outputStyle);
		
		$msg   = trim($msg);
		$msg   = $msg ? " $msg" : '';
		$title = $this->sayTitle ? "<title> $this->sayTitle </title>" : '';
		$msg   = "<fg=black;bg=bright-yellow>$this->name: </>$title$msg";
		$this->cmd->output->say($msg);
		
		return $this;
	}
}