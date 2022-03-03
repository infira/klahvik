<?php

namespace Infira\Klahvik\console;


use Symfony\Component\Console\Input\InputArgument;
use CzProject\GitPhp\Git;
use Spatie\Async\Pool;
use Infira\Klahvik\config\Config;
use Infira\console\Console;

class PhpStorm extends CommandMethod
{
	protected ?\Infira\Klahvik\config\PhpStorm $phpConfig;
	
	public function __construct(Config $config, ?string $client)
	{
		parent::__construct($config, 'storm', $client);
		$this->phpConfig = $this->client->getPhpStorm();
	}
	
	public function configure(): void
	{
		parent::configure();
		$this->addArgument('branch', InputArgument::REQUIRED);
	}
	
	
	public function make()
	{
		$clonePath = $this->phpConfig->getClonePath();
		$repoUrl   = $this->phpConfig->getRepoUrl();
		$branch    = $this->input->getArgument('branch');
		$clonePath = "$clonePath$branch";
		
		system("rm -rf $clonePath");
		Console::processRegionCommand('cloning repo', "git clone --progress --branch gar19 git@bitbucket.org:infira/gws.git $clonePath");
		exit("");
		
		foreach (range(1, 5) as $i) {
			$pool[] = async(function () use ($i)
			{
				$output = $i * 2;
				
				return $output;
			})->then(function (int $output)
			{
				echo $output . "\n";
			});
		}
		await($pool);
		
		exit;
		
		
		$repo = $this->git->cloneRepository($repoUrl, $clonePath . $branch);
		
		$pool = Pool::create();
		
		foreach (range(1, 5) as $i) {
			$pool[] = async(function () use ($i)
			{
				$output = $i * 2;
				
				return $output;
			})->then(function (int $output)
			{
				echo $output . "\n";
			});
		}
		await($pool);
		
		return true;
		$repo->createBranch($this->phpConfig->getBranchPrefix(), true);
		debug($branch);
	}
	
	
}