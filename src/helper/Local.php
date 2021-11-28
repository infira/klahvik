<?php

namespace Infira\Klahvik\helper;

use Infira\Klahvik\console\Command;
use Infira\Klahvik\config\Config;
use Infira\Utils\File;
use Symfony\Component\Process\Process;

class Local extends MachineInstance
{
	protected Config $config;
	
	public function __construct(Command &$cmd, Config $config)
	{
		$this->config = $config;
		parent::__construct('localhost', $cmd);
	}
	
	public function tmp(string $path = ''): string
	{
		return $this->config->getLocalTmpPath($path);
	}
	
	
	public function createBash(string $templateFileName, string $bashFileName, array $variables): string
	{
		$tmpl = KLAHVIK_PATH . 'src/bashTemplates/' . $templateFileName;
		if (!file_exists($tmpl))
		{
			$this->cmd->error("bash template('$tmpl') does not exist");
		}
		$content = file_get_contents($tmpl);
		foreach ($variables as $name => $value)
		{
			$content = str_replace('${' . $name . '}', $value, $content);
		}
		$bash = $this->tmp($bashFileName);
		File::delete($bash);
		File::create($bash, $content);
		
		return $bash;
	}
	
	public function createDumpDbBash(array $variables, array $ignoreTables): string
	{
		$variables['IGNORE_DATA_TABLE_STRING'] = [];
		foreach ($ignoreTables as $table)
		{
			$variables['IGNORE_DATA_TABLE_STRING'][] = '--ignore-table="' . $variables['db'] . '.' . $table . '"';
		}
		$variables['IGNORE_DATA_TABLE_STRING'] = join(' ', $variables['IGNORE_DATA_TABLE_STRING']);
		
		return $this->createBash('dumpDb.sh.template', 'dumpDb.sh', $variables);
	}
	
	public final function rsync(string $server, string $src, string $destination)
	{
		$this->execute("rsync --timeout=0 -av --progress --del $server:$src $destination");
	}
	
	public final function execute(string $command)
	{
		$process = Process::fromShellCommandline($command);
		$process->setTimeout(1800);
		$process->run(fn($type, $line) => $this->say($line));
	}
}