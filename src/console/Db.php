<?php

namespace Infira\Klahvik\console;


use Symfony\Component\Console\Input\InputOption;

class Db extends Command
{
	protected ?string $namespace = 'db';
	protected ?string $name      = 'db';
	
	private array  $databases     = [];
	private string $localDbPrefix = '';
	
	public function __construct(array $databases, string $localDbPrefix)
	{
		$this->databases     = $databases;
		$this->localDbPrefix = $localDbPrefix;
		parent::__construct();
	}
	
	public function configure(): void
	{
		$this->addOption('domain', 'd', InputOption::VALUE_OPTIONAL, 'In what domain', 'all');
		$this->addOption('branch', 'b', InputOption::VALUE_OPTIONAL, 'Into what branch', 'master');
		$this->addOption('local', 'l');
		$this->addOption('force', 'f');
		$this->addOption('del');
	}
	
	public function runCommand()
	{
		$domain = $this->input->getOption('domain');
		$branch = $this->input->getOption('branch');
		
		$loop = $domain == 'all' ? array_keys($this->databases) : [$domain];
		
		foreach ($loop as $domain)
		{
			$this->download($domain, $branch);
			$this->output->nl();
		}
	}
	
	private function download(string $domain, string $branch)
	{
		$forceDownload   = $this->input->getOption('force');
		$deleteLocalDump = $this->input->getOption('del');
		
		$localDB = $this->localDbPrefix . '_' . $branch . '_' . $domain;
		$liveDB  = $this->getLiveDB($domain);
		
		$this->region("importing domain($domain)", function () use ($deleteLocalDump, $forceDownload, $localDB, $liveDB)
		{
			$structurePath = $this->local->tmp("$liveDB.structure.sql");
			$dataPath      = $this->local->tmp("$liveDB.data.sql");
			if (!file_exists($structurePath) or !file_exists($dataPath) or $forceDownload)
			{
				$this->downloadRemoteDb($liveDB);
			}
			$this->importVagrantDb($localDB, $liveDB, $deleteLocalDump);
		});
	}
	
	private function getLiveDB(?string $domain): string
	{
		if (!isset($this->databases[$domain]))
		{
			$this->error("domain $domain not found");
		}
		
		return $this->databases[$domain];
	}
	
	protected function downloadRemoteDb(string $db)
	{
		$this->remote->title("downloading $db ", function () use ($db)
		{
			$tmpPath = $this->remote->tmp();
			$this->remote->title("dumping $db", function () use ($db, $tmpPath)
			{
				$this->remote->runKlahvikScript('dumpDb.sh', $db . ' "' . $tmpPath . '"');
			});
			$this->local->title("downloading $db", function () use ($db, $tmpPath)
			{
				$this->local->rsync($this->remote->getUserHost(), $this->remote->tmp('*.sql'), $this->local->tmp());
			});
			
			$structurePath = $this->remote->klahvikPath("tmp/$db.structure.sql");
			$dataPath      = $this->remote->klahvikPath("tmp/$db.data.sql");
			$this->remote->execute([
				"rm -f $structurePath",
				"rm -f $dataPath",
			]);
		});
	}
	
	protected function importVagrantDb(string $db, string $fromDb, bool $deleteDumpFiles = false)
	{
		$this->vagrant->title("importing db($fromDb) to ($db)", function () use ($db, $fromDb, $deleteDumpFiles)
		{
			$tmpPath = $this->vagrant->tmp();
			if (empty(trim($this->vagrant->execute('sudo mysql -e "SHOW DATABASES LIKE \'' . $db . '\'"')->getOutput())))
			{
				$this->vagrant->say("creating $db")->execute('sudo mysql -e "CREATE DATABASE IF NOT EXISTS ' . $db . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"');
			}
			else
			{
				$this->vagrant->say("droping local tables")->runKlahvikScript("dropLocalTables.sh", $db . ' "' . $tmpPath . '"');
			}
			$structureFile = $this->vagrant->tmp("$fromDb.structure.sql");
			$dataFile      = $this->vagrant->tmp("$fromDb.data.sql");
			$this->vagrant->say('mysql importing')->execute([
				"sudo mysql $db < $structureFile",
				"sudo mysql $db < $dataFile",
			]);
			if ($deleteDumpFiles)
			{
				$this->vagrant->execute([
					"sudo rm -f $structureFile",
					"sudo rm -f $dataFile",
				]);
			}
		});
	}
}