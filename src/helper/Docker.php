<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Output\ConsoleOutput;
use Infira\Console\Process;
use Infira\Klahvik\config\Config;

/**
 * @property \Infira\Klahvik\config\DockerConfig $config
 */
class Docker extends Local
{
    public function __construct(ConsoleOutput $console)
    {
        parent::__construct('docker', Config::getDocker(), $console);
    }

    final public function executeMysql(string $command): Process
    {
        return $this->process(sprintf('mysql -uroot -p%s -e "%s"', $this->config->getRootPassword(), $command));
    }

    final public function importSqlFromFile(string $db, string $file): Process
    {
        return $this->process(sprintf('mysql -uroot -p%s %s < %s', $this->config->getRootPassword(), $db, $file));
    }

    protected function makeCommand(string $command): string
    {
        return sprintf('docker exec -i %s %s', $this->config->getImage(), $command);
    }
}