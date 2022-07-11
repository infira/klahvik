<?php

namespace Infira\Klahvik\console;

use Symfony\Component\Console\Input\InputArgument;

class RSync extends Command
{
	private array $folders;
	
	public function __construct(string $client)
	{
		parent::__construct('data', $client);
		$this->folders = $this->clientConfig->getRSync();
	}
	
	public function configure(): void
	{
		$this->addArgument('folder', InputArgument::OPTIONAL, 'What folder to sync', 'all');
	}
	
	public function runCommand()
	{
		if (!$this->folders) {
			Console::error("task 'rsync' config not defined");
		}
		$folder = $this->input->getArgument('folder');
		if ($folder !== 'all' and !isset($this->folders[$folder])) {
			Console::error("Folder('$folder') not defined");
		}
		$folders = $folder == 'all' ? $this->folders : [$folder => $this->folders[$folder]];
		foreach ($folders as $name => $pathStr) {
			$branches = is_string($pathStr) ? (array)trim($pathStr) : $pathStr;
			foreach ($branches as $f) {
				[$source, $dest] = array_map(fn($f) => trim($f), explode(',', $f));
				$this->local->section("rsync ('$name')", function () use ($source, $dest) {
					$this->remote->downloadFolder($source, $dest);
				});
			}
		}
	}
}