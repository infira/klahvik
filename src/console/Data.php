<?php

namespace Infira\Klahvik\console;

use Symfony\Component\Console\Input\InputArgument;
use Infira\Klahvik\config\Config;
use Infira\console\Console;

class Data extends Command
{
	private ?\Infira\Klahvik\config\Data $dataConfig;
	
	private ?\Infira\Klahvik\config\DataSync $sync;
	
	public function __construct(Config $config, string $client)
	{
		parent::__construct($config, 'data', $client);
		$this->dataConfig = $this->client->getData();
		$this->sync       = $this->dataConfig->getSync();
	}
	
	public function configure(): void
	{
		$this->addArgument('sync', InputArgument::REQUIRED);
	}
	
	public function runCommand()
	{
		if ($this->input->getArgument('sync') and !$this->sync) {
			Console::error("task 'sync' config not defined");
		}
		if ($this->input->getArgument('sync')) {
			$this->local->rsync($this->remote->getUserHost(), $this->sync->getSource(), $this->sync->getDest());
		}
	}
}