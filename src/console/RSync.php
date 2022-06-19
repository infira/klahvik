<?php

namespace Infira\Klahvik\console;

use Symfony\Component\Console\Input\InputOption;

class RSync extends Command
{
	private array $folders;
	
	public function __construct(string $client)
	{
		parent::__construct('data', $client);
		$this->folders = $this->client->getRSync();
	}
	
	public function configure(): void
	{
		$this->addOption('folder', 'f', InputOption::VALUE_OPTIONAL, 'What folder to sync', 'all');
	}
	
	public function runCommand()
	{
		if (!$this->folders) {
			Console::error("task 'rsync' config not defined");
		}
		$folder = $this->input->getOption('folder');
		if ($folder !== 'all' and !isset($this->folders[$folder])) {
			Console::error("Folder('$folder') not defined");
		}
		$folders = $folder == 'all' ? $this->folders : [$folder => $this->folders[$folder]];
		foreach ($folders as $name => $f) {
			[$source, $dest] = array_map(fn($f) => trim($f), explode(',', $f));
			$this->local->section("rsync ('$name')", function () use ($source, $dest)
			{
				$this->remote->downloadFolder($source, $dest);
			});
		}
	}
}