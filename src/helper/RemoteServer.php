<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Process;
use Infira\Klahvik\config\ServerConfig as Config;
use Spatie\Ssh\Ssh;
use Wolo\File\FileHandler;

/**
 * @property \Infira\Klahvik\config\ServerConfig $config
 */
class RemoteServer extends MachineInstance
{
    public function __construct(Config $config, private LocalHost $local, \Infira\Console\Output\ConsoleOutput $console)
    {
        parent::__construct($config->getHost(), $config, $console);
    }

    /**
     * @return string - returns user@host
     */
    public function getUserHost(): string
    {
        return sprintf("%s@%s", $this->config->getUser(), $this->config->getHost());
    }

    public function uploadProcess(string|FileHandler $source, string|FileHandler $remotePath): Process
    {
        return $this->local->process($this->ssh()->getUploadCommand((string)$source, (string)$remotePath));
    }

    public function downloadFileProcess(string|FileHandler $file, string|FileHandler $destination): Process
    {
        return $this->local->rsync($this->getUserHost(), (string)$file, (string)$destination);
    }

    public function downloadFolderProcess(string $folder, string $destination): Process
    {
        return $this->local->rsyncFolderProcess($this->getUserHost(), $folder, $destination);
    }

    protected function ssh(): Ssh
    {
        return Ssh::create($this->config->getUser(), $this->config->getHost(), $this->config->getPort());
    }

    protected function getExecuteCommand(string $command): string
    {
        return $this->ssh()->getExecuteCommand($command);
    }
}