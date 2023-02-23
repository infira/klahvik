<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Output\ConsoleOutput;
use Infira\Console\Process;
use Infira\Console\Utils;
use Infira\Klahvik\config\MachineConfig;

abstract class MachineInstance
{
    private string $name;
    private string $sayPrefix = '';

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
        $lastProcess = null;
        foreach ((array)$commands as $command) {
            $lastProcess = Process::fromShellCommandline($this->makeCommand($command));
            $lastProcess->setTimeout(1800);
            $lastProcess->setSpeaker(fn($line, $task = null) => $this->sayProcess($line, $task));
        }

        return $lastProcess;
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

    public function runKlahvikScript(string $script, string $arguments = ''): void
    {
        $arguments = $arguments ? " $arguments" : '';
        $this->process('sh '.$this->config->getKlahvikPath("bash/$script").$arguments);
    }

    protected function makeCommand(string $command): string { return $command; }

    public function execute(string|array $commands): string
    {
        $res = [];
        foreach ((array)$commands as $cmd) {
            $res[] = system($this->makeCommand($cmd));
        }

        return join("\n", $res);
    }

    public function deleteFile(string ...$files): void
    {
        foreach ($files as $file) {
            $this->execute("rm -f $file");
        }
    }
}