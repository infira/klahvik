<?php

namespace Infira\Klahvik\helper;

use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;
use Infira\Utils\Dir;
use Infira\Klahvik\console\Command;

class Server extends MachineInstance
{
	private ?string $klahvikPath;
	private ?string $tmpPath;
	private string  $user;
	private string  $host;
	private ?int    $port;
	
	public function __construct(Command &$cmd, string $user, string $host, int $port = null)
	{
		$this->cmd  = &$cmd;
		$this->user = $user;
		$this->host = $host;
		$this->port = $port;
		parent::__construct($this->host, $cmd);
	}
	
	public function setKlahvikPath(string $klahvikPath): void
	{
		$this->klahvikPath = Dir::fixPath($klahvikPath);
	}
	
	public function klahvikPath(string $path = ''): string
	{
		return $this->klahvikPath . $path;
	}
	
	public function setTmpPath(string $path)
	{
		$this->tmpPath = $path;
	}
	
	public function tmp(string $path = ''): string
	{
		return $this->tmpPath . $path;
	}
	
	private function ssh(): Ssh
	{
		return Ssh::create($this->user, $this->host, $this->port);
	}
	
	public function execute($command, callable $outputCallback = null, string $msg = null): Process
	{
		$ssh = $this->ssh();
		if ($outputCallback)
		{
			$ssh->onOutput(fn($type, $line) => $outputCallback($line));
		}
		!$msg ?: $this->say($msg);
		
		return $ssh->execute($command);
	}
	
	public function runKlahvikScript(string $script, string $arguments = '')
	{
		$arguments = $arguments ?: " $arguments";
		$bashPath  = $this->klahvikPath('bash');
		$this->execute([
			"cd $bashPath",
			"bash $script $arguments",
		], function ($line)
		{
			if (str_contains($line, 'error'))
			{
				$this->cmd->output->error($line);
				exit;
			}
			$this->say($line);
		});
	}
	
	/**
	 * @return string - returns user@host
	 */
	public function getUserHost(): string
	{
		return "$this->user@$this->host";
	}
}