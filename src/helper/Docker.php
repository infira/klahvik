<?php

namespace Infira\Klahvik\helper;

/**
 * @property \Infira\Klahvik\config\Docker $config
 */
class Docker extends Local
{
	public function __construct()
	{
		parent::__construct('docker', \Infira\Klahvik\config\Config::getDocker());
	}
	
	public final function executeMysql(string $command): Process
	{
		return $this->process(sprintf('mysql -uroot -p%s -e "%s"', $this->config->getRootPassword(), $command));
	}
	
	public final function sqlFromFile(string $db, string $file): Process
	{
		return $this->process(sprintf('mysql -uroot -p%s %s < %s', $this->config->getRootPassword(), $db, $file));
	}
	
	protected function makeCommand(string $command): string
	{
		return sprintf('docker exec -i %s %s', $this->config->getImage(), $command);
	}
}