<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Process;
use Infira\Klahvik\config\ServerConfig as Config;
use Spatie\Ssh\Ssh;

/**
 * @property \Infira\Klahvik\config\ServerConfig $config
 */
class Server extends MachineInstance
{
    public function __construct(Config $config, private Local $local, \Infira\Console\Output\ConsoleOutput $console)
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

    public function uploadProcess(string $localPath, string $remotePath): Process
    {
        $userHost = $this->getUserHost();
        return $this->local->process("scp $localPath $userHost:$remotePath");
    }

    public function downloadFileProcess(string $file, string $destination): Process
    {
        return $this->local->rsync($this->getUserHost(), $file, $destination);
    }

    public function downloadFolderProcess(string $folder, string $destination): Process
    {
        return $this->local->rsyncFolderProcess($this->getUserHost(), $folder, $destination);
    }

    protected function makeCommand(string $command): string
    {
        $ssh = Ssh::create($this->config->getUser(), $this->config->getHost(), $this->config->getPort());

        return $ssh->getExecuteCommand($command);
    }
}