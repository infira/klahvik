<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Helper\ProcessMessage;
use Infira\Console\Output\ConsoleOutput;
use Infira\Console\Process;
use Infira\Klahvik\config\MachineConfig;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Wolo\File\FileHandler;
use Wolo\File\Path;
use Wolo\Str;

abstract class MachineInstance
{
    private string $name;

    public function __construct(string $name, protected MachineConfig $config, protected ConsoleOutput $console)
    {
        $this->name = $name;
        //black, red, green, yellow, blue, magenta, cyan, white, default, gray, bright-red, bright-green, bright-yellow, bright-blue, bright-magenta, bright-cyan, bright-white
        $this->console->getFormatter()->setStyle('task', new OutputFormatterStyle('bright-cyan'));
    }

    public function task(string $title, callable $between): void
    {
        $title = str_replace('{name}', '<fg=cyan>'.$this->name.'</>', $title);
        $this->console->miniRegion($title, $between);
    }

    public function process(string|array $commands): Process
    {
        $process = Process::fromShellCommandline(
            $this->getProcessCommand($commands)
        );
        $process->setTimeout(1800);
        $process->setSpeaker(
            function ($type, $processLines = null) use ($process) {
                if ($type instanceof ProcessMessage) {
                    $processLines = $type->toString();
                    $type = Process::OUT;
                }

                if (func_num_args() === 1) {
                    $processLines = $type;
                    $type = Process::OUT;
                }

                Utils::eachLine($processLines, function ($line) use ($type, $process) {
                    $line = trim($line);
                    if ($type === Process::ERR && $process->canDisplayErrors()) {
                        $line = "<fg=red>[ERROR] $line</>";
                        $process->setAsFailed()->stop(0);
                    }
                    $this->console->writeSection(
                        $process->getTask() ?: $this->name,
                        $line,
                        'task'
                    );
                });
            }
        );

        return $process;
    }

    public function rsync(string $src, string $destination, array $options = []): Process
    {
        $extraOptions = implode(' ', $options);

        return $this->process("rsync --timeout=0 -av --progress $extraOptions $src $destination");
    }

    public function folderRSync(string $src, string $destination): Process
    {
        $src = Path::slash($src);
        $destination = Path::slash($destination);
        if (!Str::endsWith($src, '*')) {
            $src .= '*';
        }

        return $this->rsync($src, $destination, ['--del']);
    }


    public function tmpPath(string ...$path): string
    {
        return $this->config->getTmpPath(...$path);
    }

    public function tmpFile(string ...$path): FileHandler
    {
        return new FileHandler($this->tmpPath(...$path));
    }

    abstract protected function getProcessCommand(string|array $command): string;

    abstract public function execute(string|array $commands): mixed;
}