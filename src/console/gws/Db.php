<?php

namespace Infira\Klahvik\console\gws;

use Symfony\Component\Console\Input\InputOption;

class Db extends \Infira\Klahvik\console\Db
{
	use RemoteConfig;
	
	protected ?string $name = 'gws';
	
	private array $databases = [
		'garmin'      => 'd79590_lvgrm',
		'intra'       => 'd79590_livint',
		'gopro'       => 'd79590_lvgpr',
		'gps24'       => 'd79590_lvgps24',
		'gpseesti'    => 'd79590_gpseesti',
		'luxify'      => 'd79590_luxify',
		'meremaailm'  => 'd79590_lvmm',
		'miiego'      => 'd79590_miiego',
		'nutistuudio' => 'd79590_lvnut',
		'oakley'      => 'd79590_oakley',
	];
	
	public function configure(): void
	{
		$this->addOption('domain', 'd', InputOption::VALUE_OPTIONAL, 'In what domain', 'all');
		$this->addOption('branch', 'b', InputOption::VALUE_OPTIONAL, 'Into what branch', 'live');
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
		
		$localDB = 'gws_' . $branch . '_' . $domain;
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
}