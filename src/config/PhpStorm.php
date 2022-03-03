<?php

namespace Infira\Klahvik\config;

use Wolo\File\Path;

class PhpStorm extends Manager
{
	public function __construct(array $realConfig, string $parentInstance = '')
	{
		$configConfig = [
			'repoUrl'      => 'string',
			'clonePath'    => 'path',
			'branchPrefix' => 'string',
		];
		parent::__construct('phps', $parentInstance, $configConfig, $realConfig);
	}
	
	public function getRepoUrl(): string
	{
		return $this->get('repoUrl');
	}
	
	public function getClonePath(string $path = ''): string
	{
		return Path::join($this->get('clonePath'), $path);
	}
	
	public function getBranchPrefix(): string
	{
		return $this->get('repoUrl');
	}
	
}