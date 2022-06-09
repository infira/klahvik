<?php

namespace Infira\Klahvik\helper;

use Infira\Klahvik\config\Server as Config;
use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;

/**
 * @property \Infira\Klahvik\config\Server $config
 */
class Server extends MachineInstance
{
	private Local $local;
	
	public function __construct(Config $config, Local $local)
	{
		parent::__construct($config->getHost(), $config);
		$this->local = $local;
	}
	
	public function execute($command, callable $outputCallback = null, string $msg = null): Process
	{
		$ssh = Ssh::create($this->config->getUser(), $this->config->getHost(), $this->config->getPort());
		if ($outputCallback) {
			$ssh->onOutput(fn($type, $line) => $outputCallback($line));
		}
		!$msg ?: $this->say($msg);
		
		return $ssh->execute($command);
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
		$this->local->process("scp $localPath $userHost:$remotePath")->say();
	}
}