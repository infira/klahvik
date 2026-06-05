<?php

namespace Infira\Klahvik;

use Infira\Console\Exceptions\ConsoleRuntimeException;
use Infira\Klahvik\Config\PhpStormConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Wolo\File\File;
use Wolo\File\Folder;
use Wolo\File\Path;

class PhpStormCommand extends Command
{
    private PhpStormConfig $config;

    public function __construct(?string $client)
    {
        parent::__construct('storm', $client);
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('project', InputArgument::OPTIONAL, 'What project to clone', null);
        $this->addOption('branch', 'b', InputOption::VALUE_OPTIONAL, 'Into what branch', 'master');
        $this->addOption('folderName', 'f', InputOption::VALUE_OPTIONAL);
    }

    public function runCommand(): void
    {
        $project = $this->input->getArgument('project') ?: $this->clientConfig->storm()->get('defaultProject');
        $this->config = $this->clientConfig->getPhpStorm($project);
        $gitUrl = $this->config->getGit();
        $branch = $this->input->getOption('branch');
        $folderName = $this->input->getOption('folderName') ?: $branch;
        $clonePath = $this->clonePath();
        if (is_dir($clonePath)) {
            throw new ConsoleRuntimeException("Clonepath('$clonePath') already exists");
        }

        $this->console->miniRegion("cloing $branch", function () use ($gitUrl, $branch, $clonePath) {
            $this->local->process("git clone --progress --branch $branch $gitUrl $clonePath")->speak();
        });

        Folder::make($this->clonePath('.idea'));
        $this->copyFolder($this->config->getIdeConfigPath(), $this->clonePath('.idea'));
        File::put($this->clonePath('.idea/.name'), "$project-".strtoupper($folderName));

        //install composer
        $composerJson = realpath($this->clonePath($this->config->getComposerJson()));
        if (file_exists($composerJson)) {
            $this->console->miniRegion("installing composer", function () use ($composerJson) {
                $path = dirname($composerJson);
                $this->local->process("cd $path && ".$this->config->getComposer()." install")->speak();
            });
        }
    }

    private function copyFolder(string $source, string $target)
    {
        collect(Folder::content($source))
            ->each(function ($file) use ($target) {
                $np = Path::join($target, basename($file));
                if (is_dir($file)) {
                    Folder::make($np);
                    $this->copyFolder($file, $np);
                }
                else {
                    copy($file, $np);
                }
            });
    }

    private function getFolderName()
    {
        $branch = $this->input->getOption('branch');

        return $this->input->getOption('folderName') ?: $branch;
    }

    private function clonePath(string $path = ''): string
    {
        return $this->config->getClonePath(Path::join($this->getFolderName(), $path));
    }

}