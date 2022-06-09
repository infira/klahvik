<?php

namespace Infira\Klahvik\console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Data extends Command
{
	private ?\Infira\Klahvik\config\Data $dataConfig;
	
	private array $sync;
	
	public function __construct(string $client)
	{
		parent::__construct('data', $client);
		$this->dataConfig = $this->client->getData();
		$this->sync       = $this->dataConfig->getSync();
	}
	
	public function configure(): void
	{
		$this->addArgument('sync', InputArgument::REQUIRED);
		$this->addOption('folder', 'f', InputOption::VALUE_OPTIONAL, 'What folder to sync', 'all');
	}
	
	public function runCommand()
	{
		if ($this->input->getArgument('sync') and !$this->sync) {
			Console::error("task 'sync' config not defined");
		}
		if ($this->input->getArgument('sync')) {
			$folder = $this->input->getOption('folder');
			if ($folder !== 'all' and !isset($this->sync[$folder])) {
				Console::error("Folder('$folder') not defined");
			}
			$folders = $folder == 'all' ? $this->sync : [$this->sync[$folder]];
			foreach ($folders as $f) {
				[$source, $dest] = array_map(fn($f) => trim($f), explode(',', $f));
				$this->local->rsync($this->remote->getUserHost(), $source, $dest);
			}
		}
	}
}