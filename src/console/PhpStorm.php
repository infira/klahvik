<?php

namespace Infira\Klahvik\console;


use Symfony\Component\Console\Input\InputArgument;
use CzProject\GitPhp\Git;
use Infira\Utils\Dir;
use Spatie\Async\Pool;
use Symfony\Component\Process\Process;

class PhpStorm extends CommandMethod
{
	protected ?string $namespace = 'storm';
	protected ?string $name      = 'storm';
	
	public function configure(): void
	{
		parent::configure();
		$this->addArgument('branch', InputArgument::REQUIRED);
		$this->opt('branchPrefix', 'gar');
	}
	
	
	public function make()
	{
		$requiredConfig = ['repoUrl', 'clonePath'];
		foreach ($requiredConfig as $cn)
		{
			if (!$this->opt($cn))
			{
				$this->error("$cn is not configured");
			}
		}
		$clonePath = Dir::fixPath($this->opt('clonePath'));
		$repoUrl   = $this->opt('repoUrl');
		$branch    = $this->input->getArgument('branch');
		$clonePath = "$clonePath$branch";
		
		system("rm -rf $clonePath");
		$this->processRegionCommand('cloning repo',"git clone --progress --branch gar19 git@bitbucket.org:infira/gws.git $clonePath");
		exit("");
		
		foreach (range(1, 5) as $i)
		{
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
		
		foreach (range(1, 5) as $i)
		{
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
		$repo->createBranch($this->opt('branchPrefix'), true);
		debug($branch);
	}
	
	
}