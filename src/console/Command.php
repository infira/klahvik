<?php

namespace Infira\Klahvik\console;

use Infira\Klahvik\config\Client;
use Infira\Klahvik\config\Config;
use Infira\Klahvik\helper\Docker;
use Infira\Klahvik\helper\Local;
use Infira\Klahvik\helper\Server;

class Command extends \Infira\console\Command
{
	protected Server $remote;
	protected Server $vagrant;
	protected Local  $local;
	protected Docker $docker;
	protected Client $client;
	
	public function __construct(string $command, ?string $client)
	{
		if ($client and $command) {
			$this->client = Config::getClient($client);
			parent::__construct("$command:$client");
		}
		else {
			parent::__construct($command);
		}
	}
	
	protected function configureExecute()
	{
		$this->configureRemote();
		$this->local  = new Local('localhost', Config::getLocal());
		$this->docker = new Docker();
		
		$this->vagrant = new Server(Config::getVagrant(), $this->local);
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