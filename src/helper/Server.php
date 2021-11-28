<?php

namespace Infira\Klahvik\helper;

use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;
use Infira\Klahvik\console\Command;
use Infira\Klahvik\config\Server as Config;

class Server extends MachineInstance
{
	private Config $config;
	private Local  $local;
	
	public function __construct(Command &$cmd, Config $config, Local $local)
	{
		$this->cmd    = &$cmd;
		$this->config = &$config;
		parent::__construct($this->config->getHost(), $cmd);
		$this->local = $local;
	}
	
	public function klahvikPath(string $path = ''): string
	{
		return $this->config->getKlahvikPath($path);
	}
	
	public function tmp(string $path = ''): string
	{
		return $this->config->getTmpPath($path);
	}
	
	private function ssh(): Ssh
	{
		return Ssh::create($this->config->getUser(), $this->config->getHost(), $this->config->getPort());
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
	
	public function runBash(string $scriptPath, string $arguments = '')
	{
		$arguments = $arguments ?: " $arguments";
		$bashPath  = dirname($scriptPath);
		$script    = basename($scriptPath);
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
	
	public function runKlahvikScript(string $script, string $arguments = '')
	{
		$this->runBash($this->klahvikPath("bash/$script"), $arguments);
	}
	
	/**
	 * @return string - returns user@host
	 */
	public function getUserHost(): string
	{
		return sprintf("%s@%s", $this->config->getUser(), $this->config->getHost());
	}
	
	
	public function upload(string $localPath, string $remotePath)
	{
		$userHost = $this->getUserHost();
		$this->local->execute("scp $localPath $userHost:$remotePath");
	}
}