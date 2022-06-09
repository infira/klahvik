<?php

namespace Infira\Klahvik\helper;

use Infira\Klahvik\config\Machine;
use Infira\Klahvik\console\Console;
use Infira\Klahvik\config\Config;
use Wolo\File\File;

class Local extends MachineInstance
{
	public function __construct(string $name, Machine $config)
	{
		parent::__construct($name, $config);
	}
	
	public function createBash(string $templateFileName, string $bashFileName, array $variables): string
	{
		$tmpl = KLAHVIK_PATH . 'src/bashTemplates/' . $templateFileName;
		if (!file_exists($tmpl)) {
			Console::error("bash template('$tmpl') does not exist");
		}
		$content = file_get_contents($tmpl);
		foreach ($variables as $name => $value) {
			$content = str_replace('${' . $name . '}', $value, $content);
		}
		$bash = Config::getLocalTmpPath($bashFileName);
		File::delete($bash);
		File::put($bash, $content);
		
		return $bash;
	}
	
	public function createDumpDbBash(array $variables, array $ignoreTables): string
	{
		$variables['IGNORE_DATA_TABLE_STRING'] = [];
		foreach ($ignoreTables as $table) {
			$variables['IGNORE_DATA_TABLE_STRING'][] = '--ignore-table="' . $variables['db'] . '.' . $table . '"';
		}
		$variables['IGNORE_DATA_TABLE_STRING'] = join(' ', $variables['IGNORE_DATA_TABLE_STRING']);
		
		return $this->createBash('dumpDb.sh.template', 'dumpDb.sh', $variables);
	}
	
	public function rsync(string $server, string $src, string $destination)
	{
		$this->process("rsync --timeout=0 -av --progress --del $server:$src $destination")->say();
	}
	
	public function execute(string|array $commands): string
	{
		$res = [];
		foreach ((array)$commands as $cmd) {
			$res[] = system($this->makeCommand($cmd));
		}
		
		return join("\n", $res);
	}
}