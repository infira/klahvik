<?php

namespace Infira\Klahvik\console;


use Symfony\Component\Console\Input\InputArgument;

class CommandMethod extends Command
{
	public function configure(): void
	{
		$this->addArgument('method', InputArgument::REQUIRED);
	}
	
	public function runCommand()
	{
		if (!method_exists($this, $method = $this->input->getArgument('method')))
		{
			$this->error("Method $method does not exists");
		}
		$this->$method();
	}
}