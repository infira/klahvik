<?php

namespace Infira\Klahvik\config;

use Infira\Klahvik\Klahvik;

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
	
	public function getClonePath(string $path = '')
	{
		return Klahvik::fixPath($this->get('clonePath') . $path);
	}
	
	public function getBranchPrefix(): string
	{
		return $this->get('repoUrl');
	}
	
}