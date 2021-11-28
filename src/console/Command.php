<?php

namespace Infira\Klahvik\console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Infira\Klahvik\helper\Server;
use Infira\Klahvik\helper\Local;
use Infira\Klahvik\helper\ConsoleOutput;
use Symfony\Component\Process\Process;
use Infira\Klahvik\config\Config;
use Infira\Klahvik\config\Client;

class Command extends \Symfony\Component\Console\Command\Command
{
	public ConsoleOutput     $output;
	protected InputInterface $input;
	protected Server         $remote;
	protected Server         $vagrant;
	protected Local          $local;
	protected Config         $mainConfig;
	protected Client         $client;
	
	public function __construct(Config $config, string $command, ?string $client)
	{
		$this->mainConfig = $config;
		if ($client and $command)
		{
			$this->client = $this->mainConfig->getClient($client);
			parent::__construct("$command:$client");
		}
		else
		{
			parent::__construct($command);
		}
	}
	
	private final function configServers()
	{
		$this->configureRemote();
		$this->local   = new Local($this, $this->mainConfig);
		$this->vagrant = new Server($this, $this->mainConfig->getVagrant(), $this->local);
		$this->remote  = new Server($this, $this->client->getServer(), $this->local);
	}
	
	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		set_time_limit(7200);
		$this->output = &$output;
		$this->input  = &$input;
		$this->configServers();
		$this->configureMethod();
		$this->beforeExecute();
		$this->runCommand();
		$this->afterExecute();
		
		return $this->success();
	}
	
	public function error(string $msg)
	{
		$this->output->error($msg);
		exit;
	}
	
	public function region(string $region, callable $regionProcess)
	{
		$msg = str_repeat("=", 25);
		$msg .= "[<question> $region </question>]";
		$msg .= str_repeat("=", 25);
		$this->output->comment($msg);
		$this->output->nl();
		$regionProcess();
		$this->output->nl();
		$this->output->comment($msg);
	}
	
	public function processRegionCommand(string $regionName, string $command)
	{
		$this->region($regionName, function () use ($regionName, $command)
		{
			$sectiion = $this->output->section();
			$process  = Process::fromShellCommandline($command);
			$process->setTimeout(1800);
			$process->start();
			$process->wait(function ($type, $buffer) use ($regionName, $sectiion)
			{
				$buffer = trim($buffer);
				if (str_contains($buffer, '%'))
				{
					$sectiion->overwrite("<comment>$regionName</comment>: " . $buffer);
					//$this->output->cl()->write("<comment>$regionName</comment>: " . trim($buffer));
					//$this->output->cl()->msg($line);
					//$this->output->cl()->write($line);
				}
				else
				{
					$this->output->msg($buffer);
				}
			});
		});
	}
	
	protected function success(): int
	{
		return \Symfony\Component\Console\Command\Command::SUCCESS;
	}
	
	protected function configureMethod() { }
	
	protected function beforeExecute() { }
	
	protected function afterExecute()
	{
		//void
	}
	
	protected function configureRemote() { }
}