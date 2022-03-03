<?php

namespace Infira\Klahvik\console;


use Symfony\Component\Console\Input\InputOption;
use Infira\Klahvik\config\Config;
use Wolo\File\File;
use Symfony\Component\Console\Input\InputArgument;
use Infira\console\Console;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class Db extends Command
{
	private \Infira\Klahvik\config\Db $dbConfig;
	
	public function __construct(Config $config, string $client)
	{
		parent::__construct($config, 'db', $client);
		$this->dbConfig = $this->client->getDb();
	}
	
	public function configure(): void
	{
		$this->addArgument('localDb', InputArgument::OPTIONAL);
		$this->addOption('project', 'p', InputOption::VALUE_OPTIONAL, 'What project to download', 'all');
		$this->addOption('branch', 'b', InputOption::VALUE_OPTIONAL, 'Into what branch', 'master');
		$this->addOption('local', 'l');
		$this->addOption('force', 'f');
		$this->addOption('del');
	}
	
	public function runCommand()
	{
		$projects = $this->input->getOption('project');
		$branch   = $this->input->getOption('branch');
		
		foreach (explode(',', $projects) as $project) {
			if (!$this->dbConfig->projectExists($project)) {
				Console::error("project project('$project') not found");
			}
			
			$loop = $project == 'all' ? $this->dbConfig->getProjectNames() : [$project];
			foreach ($loop as $project) {
				$this->import($project, $branch);
				Console::nl();
			}
		}
	}
	
	private function import(string $project, string $branch)
	{
		$forceDownload   = $this->input->getOption('force');
		$deleteLocalDump = $this->input->getOption('del');
		
		$localDB = $this->input->getArgument('localDb') ? $this->input->getArgument('localDb') : $this->dbConfig->getLocalName($branch, $project);
		$liveDB  = $this->dbConfig->getRemoteName($project);
		
		Console::region("importing project('$project')", function () use ($deleteLocalDump, $forceDownload, $localDB, $liveDB)
		{
			$structurePath = $this->local->tmp("$liveDB.tar.gz");
			if (!file_exists($structurePath) or $forceDownload) {
				$this->downloadRemoteDb($liveDB);
			}
			$this->importToVagrant($localDB, $liveDB, $deleteLocalDump);
		});
	}
	
	protected function downloadRemoteDb(string $db)
	{
		$this->remote->section("downloading $db ", function () use ($db)
		{
			$tmpPath = $this->remote->tmp();
			$this->remote->section("dumping $db", function () use ($db, $tmpPath)
			{
				$dumpBash   = $this->local->createDumpDbBash([
					'db'       => $db,
					'tempPath' => $tmpPath,
				], $this->dbConfig->getVoidDataDumpTables());
				$remoteBash = $this->remote->tmp('dumpDb.sh');
				
				$this->remote->upload($dumpBash, $remoteBash);
				File::delete($dumpBash);
				$this->remote->runBash($remoteBash, $db . ' "' . $tmpPath . '"');
				$this->remote->execute("rm -f $remoteBash");
			});
			$this->local->section("downloading $db", function () use ($db, $tmpPath)
			{
				$this->local->rsync($this->remote->getUserHost(), $this->remote->tmp("$db.tar.gz"), $this->local->tmp());
			});
			
			$this->remote->execute([
				"rm -f " . $this->remote->tmp("$db.structure.sql"),
				"rm -f " . $this->remote->tmp("$db.data.sql"),
				"rm -f " . $this->remote->tmp("$db.tar.gz"),
			]);
		});
	}
	
	protected function importToVagrant(string $db, string $fromDb, bool $deleteDumpFiles = false)
	{
		$this->local->section("unpacking tar", function () use ($db, $fromDb, $deleteDumpFiles)
		{
			$this->local->execute(sprintf(' tar -xvf %s -C %s', $this->local->tmp("$fromDb.tar.gz"), $this->local->tmp()));
		});
		$outputStyle = new OutputFormatterStyle('green');
		Console::$output->getFormatter()->setStyle('db', $outputStyle);
		$this->vagrant->section("importing  db(<db>$fromDb</db>) to (<db>$db</db>)", function () use ($db, $fromDb, $deleteDumpFiles)
		{
			$tmpPath = $this->vagrant->tmp();
			if (empty(trim($this->vagrant->execute('sudo mysql -e "SHOW DATABASES LIKE \'' . $db . '\'"')->getOutput()))) {
				$this->vagrant->say("creating $db")->execute('sudo mysql -e "CREATE DATABASE IF NOT EXISTS ' . $db . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"');
			}
			else {
				$this->vagrant->say("droping local tables")->runKlahvikScript("dropLocalTables.sh", $db . ' "' . $tmpPath . '"');
			}
			$structureFile = $this->vagrant->tmp("$fromDb.structure.sql");
			$dataFile      = $this->vagrant->tmp("$fromDb.data.sql");
			$this->vagrant->say('mysql importing')->execute([
				"sudo mysql $db < $structureFile",
				"sudo mysql $db < $dataFile",
				"sudo rm -f $structureFile",
				"sudo rm -f $dataFile",
			]);
		});
		if ($deleteDumpFiles) {
			$this->local->execute(sprintf('rm -f %s', $this->local->tmp("$fromDb.tar.gz")));
		}
	}
}