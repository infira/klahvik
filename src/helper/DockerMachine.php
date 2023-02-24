<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Output\ConsoleOutput;
use Infira\Console\Process;
use Infira\Klahvik\config\Config;
use Infira\Klahvik\config\DockerConfig;

/**
 * @property DockerConfig $config
 */
class DockerMachine extends LocalHost
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

    protected function getExecuteCommand(string|array $command): string
    {
        $commandString = implode(PHP_EOL, (array)$command);
        $delimiter = 'EOF-KLAHVIK-LOCAL-CMD';

        $image = $this->config->getImage();

        return "docker exec -i $image << $delimiter".PHP_EOL
            .$commandString.PHP_EOL
            .$delimiter;
    }
}