<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Output\ConsoleOutput;
use Infira\Console\Process;
use Infira\Klahvik\config\Config;
use Infira\Klahvik\config\DockerConfig;
use Wolo\File\FileHandler;

/**
 * @property DockerConfig $config
 */
class DockerMachine extends LocalHost
{
    public function __construct(ConsoleOutput $console)
    {
        parent::__construct('docker', Config::getDocker(), $console);
    }

    private function prepareMysqlCommand(string $extra = ''): string
    {
        return sprintf('mysql -uroot -p%s%s', $this->config->getRootPassword(), ($extra ? " $extra" : ''));
    }

    final public function mysqlQuery(string|array $query): Process
    {
        return $this->process(
            array_map(fn($q) => $this->prepareMysqlCommand('-e "'.$q.'"'),
                (array)$query)
        );
    }

    final public function mysqlQueryFromFile(string $db, string|FileHandler|array $files): Process
    {
        return $this->process(
            array_map(
                fn($sql) => $this->prepareMysqlCommand("$db < $sql"),
                (array)$files,
            )
        );
    }

    protected function getProcessCommand(string|array $command): string
    {
        $image = $this->config->getImage();

        return implode(
            ' && ',
            array_map(
                static fn(string $cmd) => "docker exec -i $image $cmd",
                (array)$command
            )
        );
    }
}