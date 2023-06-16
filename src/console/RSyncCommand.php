<?php

namespace Infira\Klahvik\console;

use Infira\Console\ConsoleRuntimeException;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;

class RSyncCommand extends Command
{
    private array $folders;

    public function __construct(string $client)
    {
        parent::__construct('data', $client);
    }

    public function configure(): void
    {
        parent::configure();
        $this->folders = $this->clientConfig->getRSync();
        $this->addArgument(
            'folders',
            InputArgument::IS_ARRAY,
            'What folder names to sync',
            array_keys($this->folders),
            function (CompletionInput $input) {
                return array_keys($this->folders);
            }
        );
    }

    public function runCommand()
    {
        if (!$this->folders) {
            throw new ConsoleRuntimeException("task 'rsync' config not defined");
        }
        $runFolders = $this->input->getArgument('folders');
        $count = count($runFolders);
        $this->console->region("rsync", function () use ($runFolders, $count) {
            $sync = function ($pathStr) {
                $branches = is_string($pathStr) ? (array)trim($pathStr) : $pathStr;
                foreach ($branches as $f) {
                    [$source, $dest] = array_map(static fn($f) => trim($f), explode(',', $f));
                    $this->remote->downloadFolder($source, $dest)->runTask("syncing $f");
                }
            };
            foreach ($runFolders as $name) {
                $pathStr = $this->folders[$name];
                if ($count > 1) {
                    $this->console->miniRegion($name, fn() => $sync($pathStr), 10);
                }
                else {
                    $sync($pathStr);
                }
            }
        }, $count === 1 ? 10 : null);
    }
}