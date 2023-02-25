<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Output\ConsoleOutput;
use Infira\Console\Process;
use Infira\Klahvik\config\ServerConfig as Config;
use Spatie\Ssh\Ssh;
use Wolo\File\FileHandler;

/**
 * @property Config $config
 */
class RemoteServer extends MachineInstance
{
    public function __construct(Config $config, private readonly LocalHost $local, ConsoleOutput $console)
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

    public function getRsyncPath(string|FileHandler $path): string
    {
        $server = $this->getUserHost();

        return "$server:$path";
    }

    public function upload(string|FileHandler $localPath, string|FileHandler $remotePath): Process
    {
        return $this->local->rsync(
            (string)$localPath,
            $this->getRsyncPath($remotePath),
        );
    }

    public function download(string|FileHandler $remotePath, string|FileHandler $localPath, string $taskName = null): Process
    {
        return $this->local->rsync(
            $this->getRsyncPath($remotePath),
            (string)$localPath
        );
    }

    public function downloadFolder(string|FileHandler $remotePath, string|FileHandler $localPath): Process
    {
        return $this->local->folderRSync($this->getRsyncPath($remotePath), $localPath);
    }

    public function deleteFile(string ...$files): void
    {
        if (!$files) {
            return;
        }
        $commands = [];
        foreach ($files as $file) {
            $commands[] = "rm -f $file";
        }
        $this->execute($commands);
    }

    public function execute(array|string $commands, string $taskName = null): Process
    {
        $process = $this->process($commands);
        $process->withTask($taskName);
        $process->run();

        return $process;
    }

    protected function ssh(): Ssh
    {
        return Ssh::create($this->config->getUser(), $this->config->getHost(), $this->config->getPort());
    }

    protected function getProcessCommand(string|array $command): string
    {
        return $this->ssh()->getExecuteCommand($command);
    }
}