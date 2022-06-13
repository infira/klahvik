<?php

namespace Infira\Klahvik\console;


use Infira\Klahvik\config\Config;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Wolo\File\File;

class Db extends Command
{
	private \Infira\Klahvik\config\Db $dbConfig;
	
	public function __construct(string $client)
	{
		parent::__construct('db', $client);
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
			foreach ($loop as $lProject) {
				$this->import($lProject, $branch);
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
			$structurePath = Config::getLocalTmpPath("$liveDB.tar.gz");
			if (!file_exists($structurePath) or $forceDownload) {
				$this->downloadRemoteDb($liveDB);
			}
			//$this->importToVagrant($localDB, $liveDB, $deleteLocalDump);
			$this->importToDocker($localDB, $liveDB, $deleteLocalDump);
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
				$this->remote->process("sh $remoteBash $db $dumpBash")->say();
				$this->remote->process("rm -f $remoteBash")->say();
			});
			$this->local->section("downloading $db", function () use ($db, $tmpPath)
			{
				$this->local->rsync($this->remote->getUserHost(), $this->remote->tmp("$db.tar.gz"), Config::getLocalTmpPath());
			});
			
			$this->remote->process([
				"rm -f " . $this->remote->tmp("$db.structure.sql"),
				"rm -f " . $this->remote->tmp("$db.data.sql"),
				"rm -f " . $this->remote->tmp("$db.tar.gz"),
			]);
		});
	}
	
	protected function importToDocker(string $db, string $fromDb, bool $deleteDumpFiles = false)
	{
		$this->local->section("unpacking tar", function () use ($db, $fromDb, $deleteDumpFiles)
		{
			$this->local->process(sprintf(' tar -xvf %s -C %s', Config::getLocalTmpPath("$fromDb.tar.gz"), Config::getLocalTmpPath()))->say();
		});
		$outputStyle = new OutputFormatterStyle('green');
		Console::$output->getFormatter()->setStyle('db', $outputStyle);
		$this->docker->section("importing  db(<db>$fromDb</db>) to (<db>$db</db>)", function () use ($db, $fromDb, $deleteDumpFiles)
		{
			$this->docker->say("droping old $db")->executeMysql('DROP DATABASE IF EXISTS ' . $db)->run();
			$this->docker->say("creating $db")->executeMysql('CREATE DATABASE ' . $db . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')->run();
			$structureFile = $this->docker->tmp("$fromDb.structure.sql");
			$dataFile      = $this->docker->tmp("$fromDb.data.sql");
			$this->docker->say('mysql importing');
			$this->docker->sqlFromFile($db, $structureFile)->say();
			$this->docker->sqlFromFile($db, $dataFile)->say();
			$this->local->execute("rm -rf $structureFile");
			$this->local->execute("rm -rf $dataFile");
		});
		if ($deleteDumpFiles) {
			$this->local->execute(sprintf('rm -f %s', Config::getLocalTmpPath("$fromDb.tar.gz")));
		}
	}
	
	protected function importToVagrant(string $db, string $fromDb, bool $deleteDumpFiles = false)
	{
		$this->local->section("unpacking tar", function () use ($db, $fromDb, $deleteDumpFiles)
		{
			$this->local->process(sprintf(' tar -xvf %s -C %s', Config::getLocalTmpPath("$fromDb.tar.gz"), Config::getLocalTmpPath()))->say();
		});
		$outputStyle = new OutputFormatterStyle('green');
		Console::$output->getFormatter()->setStyle('db', $outputStyle);
		$this->vagrant->section("importing  db(<db>$fromDb</db>) to (<db>$db</db>)", function () use ($db, $fromDb, $deleteDumpFiles)
		{
			$tmpPath = $this->vagrant->tmp();
			if (empty(trim($this->vagrant->process('sudo mysql -e "SHOW DATABASES LIKE \'' . $db . '\'"')->getOutput()))) {
				$this->vagrant->say("creating $db")->process('sudo mysql -e "CREATE DATABASE IF NOT EXISTS ' . $db . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"');
			}
			else {
				$this->vagrant->say("droping local tables")->runKlahvikScript("dropLocalTables.sh", $db . ' "' . $tmpPath . '"');
			}
			$structureFile = $this->vagrant->tmp("$fromDb.structure.sql");
			$dataFile      = $this->vagrant->tmp("$fromDb.data.sql");
			$this->vagrant->say('mysql importing')->process([
				"sudo mysql $db < $structureFile",
				"sudo mysql $db < $dataFile",
				"sudo rm -f $structureFile",
				"sudo rm -f $dataFile",
			]);
		});
		if ($deleteDumpFiles) {
			$this->local->execute(sprintf('rm -f %s', Config::getLocalTmpPath("$fromDb.tar.gz")));
		}
	}
}