<?php

namespace Infira\Klahvik\console;

use Infira\Console\Console;
use Symfony\Component\Console\Input\InputArgument;

class RSyncCommand extends Command
{
    private array $folders;

    public function __construct(string $client)
    {
        parent::__construct('data', $client);
        $this->folders = $this->clientConfig->getRSync();
    }

    public function configure(): void
    {
        $this->addArgument('folder', InputArgument::OPTIONAL, 'What folder to sync', 'all');
    }

    public function runCommand()
    {
        if (!$this->folders) {
            Console::error("task 'rsync' config not defined");
        }
        $folder = $this->input->getArgument('folder');
        if ($folder !== 'all' && !isset($this->folders[$folder])) {
            Console::error("Folder('$folder') not defined");
        }
        $folders = $folder === 'all' ? $this->folders : [$folder => $this->folders[$folder]];
        $count = count($folders);
        $this->output->region("rsync ('$folder')", function () use ($folders, $count) {
            $sync = function ($pathStr) {
                $branches = is_string($pathStr) ? (array)trim($pathStr) : $pathStr;
                foreach ($branches as $f) {
                    [$source, $dest] = array_map(static fn($f) => trim($f), explode(',', $f));
                    $this->remote->downloadFolder($source, $dest)->runTask("syncing $f");
                }
            };
            foreach ($folders as $name => $pathStr) {
                if ($count > 1) {
                    $this->output->miniRegion($name, fn() => $sync($pathStr), 10);
                }
                else {
                    $sync($pathStr);
                }
            }
        }, $count === 1 ? 10 : null);
    }
}