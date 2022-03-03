<?php

namespace Infira\Klahvik\config;

class Db extends Manager
{
	public function __construct(array $realConfig, string $parentInstance = '')
	{
		$configConfig = [
			'localNameTemplate'  => 'string',
			'projects'           => 'array',
			'voidDataDumpTables' => 'array',
		];
		parent::__construct('db', $parentInstance, $configConfig, $realConfig);
	}
	
	
	public function getLocalName(string $branch, string $project): string
	{
		$name = $this->get('localNameTemplate');
		$name = str_replace('{branch}', $branch, $name);
		$name = str_replace('{project}', $project, $name);
		
		return trim($name);
	}
	
	public function getProjectNames(): array
	{
		return array_keys($this->get('projects'));
	}
	
	public function projectExists(string $project): bool
	{
		return $project == 'all' || isset($this->get('projects')[$project]);
	}
	
	public function getRemoteName(string $project): string
	{
		$projects = $this->get('projects');
		if (!isset($projects[$project]))
		{
			$this->error('projects', "project('$project') does not exist");
		}
		
		return $projects[$project];
	}
	
	public function getVoidDataDumpTables(): array
	{
		$tables = $this->get('voidDataDumpTables');
		array_walk($tables, fn($table) => trim($table));
		
		return $tables;
	}
	
}