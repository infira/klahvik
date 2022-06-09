<?php

namespace Infira\Klahvik\helper;

use Infira\Klahvik\console\Console;

class Process extends \Symfony\Component\Process\Process
{
	/**
	 * @var callable|null
	 */
	private $speaker = null;
	
	public function say(): static
	{
		$speaker = $this->speaker ?: fn($line) => Console::say($line);
		$this->run(fn($type, $line) => $speaker($line));
		
		return $this;
	}
	
	public function setSpeaker(callable $speaker)
	{
		$this->speaker = $speaker;
	}
	
	public function getOutput(): string
	{
		$this->run();
		
		return parent::getOutput();
	}
}