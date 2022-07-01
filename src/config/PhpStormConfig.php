<?php

namespace Infira\Klahvik\config;

use Wolo\File\Path;

class PhpStormConfig extends ConfigCollection
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
		return $this->getReal('composer', Config::getComposer());
	}
	
	public function getComposerJson(): string
	{
		return $this->getReal('composerJson', './composer.json');
	}
}