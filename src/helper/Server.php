<?php

namespace Infira\Klahvik\helper;

use Infira\Klahvik\config\Server as Config;
use Spatie\Ssh\Ssh;

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
	
	protected function makeCommand(string $command): string
	{
		$ssh = Ssh::create($this->config->getUser(), $this->config->getHost(), $this->config->getPort());
		
		return $ssh->getExecuteCommand($command);
	}
}