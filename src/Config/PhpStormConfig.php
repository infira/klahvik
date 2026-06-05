<?php

namespace Infira\Klahvik\Config;
use Infira\Console\Config;
use Wolo\File\Path;

class PhpStormConfig extends Config
{
	public function getGit(): string
	{
		return $this->get('git');
	}
	
	public function getClonePath(string $path = ''): string
	{
		return Path::join($this->getPath('clonePath'), $path);
	}
	
	public function getIdeConfigPath(string $path = ''): string
	{
		return Path::join($this->getPath('ideConfig'), $path);
	}
	
	public function getComposer(): string
	{
		return $this->get('composer', Klahvik::getComposer());
	}
	
	public function getComposerJson(): string
	{
		return $this->get('composerJson', './composer.json');
	}
}