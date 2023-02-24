<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Output\ConsoleOutput;
use Infira\Console\Process;
use Infira\Console\Utils;
use Infira\Klahvik\config\MachineConfig;
use Wolo\File\File;

abstract class MachineInstance
{
    private string $name;

    public function __construct(string $name, protected MachineConfig $config, protected ConsoleOutput $console)
    {
        $this->name = $name;
    }

    public function task(string $title, callable $between): void
    {
        $this->console->miniRegion($title, $between);
    }

    public function process(string|array $commands): Process
    {
        $process = Process::fromShellCommandline($this->getExecuteCommand($commands));
        $process->setTimeout(1800);
        $process->setSpeaker(fn($line, $task = null) => $this->sayProcess($line, $task));

        return $process;
    }

    public function sayProcess(string $processLines = '', string $task = null): static
    {
        Utils::eachLine($processLines, function ($line) use ($task) {
            $line = trim($line);
            $task = $task ?: $this->name;
            $this->console->writeSection($task, $line);
        });

        return $this;
    }

    public function tmpPath(string $path = ''): string
    {
        return $this->config->getTmpPath($path);
    }

    abstract protected function getExecuteCommand(string $command): string;

    public function deleteFile(string ...$files): void
    {
        File::removeIfExists($files);
    }
}