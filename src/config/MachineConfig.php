<?php

namespace Infira\Klahvik\config;

use Wolo\File\Path;

class MachineConfig extends ConfigCollection
{
	public function getKlahvikPath(string $path = ''): string
	{
		return Path::join($this->getPath('klahvikPath'), $path);
	}
	
	public function getTmpPath(string $path = ''): string
	{
		if (!$this->has('tmpPath')) {
			return $this->getKlahvikPath("tmp/$path");
		}
		
		return Path::join($this->getPath('tmpPath'), $path);
	}
}