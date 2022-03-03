<?php

namespace Infira\Klahvik\console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Infira\Klahvik\helper\Server;
use Infira\Klahvik\helper\Local;
use Symfony\Component\Process\Process;
use Infira\Klahvik\config\Config;
use Infira\Klahvik\config\Client;

class Command extends \Infira\console\Command
{
	protected Server $remote;
	protected Server $vagrant;
	protected Local  $local;
	protected Config $mainConfig;
	protected Client $client;
	
	public function __construct(Config $config, string $command, ?string $client)
	{
		$this->mainConfig = $config;
		if ($client and $command) {
			$this->client = $this->mainConfig->getClient($client);
			parent::__construct("$command:$client");
		}
		else {
			parent::__construct($command);
		}
	}
	
	protected function configureExecute()
	{
		$this->configureRemote();
		$this->local   = new Local($this, $this->mainConfig);
		$this->vagrant = new Server($this->mainConfig->getVagrant(), $this->local);
		$this->remote  = new Server($this->client->getServer(), $this->local);
		$this->configureMethod();
	}
	
	protected function configureMethod() {}
	
	protected function beforeExecute() {}
	
	protected function afterExecute()
	{
		//void
	}
	
	protected function configureRemote() {}
}