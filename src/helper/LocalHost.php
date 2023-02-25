<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Console;
use Infira\Console\Process;
use Wolo\File\FileHandler;
use Wolo\File\Path;
use Wolo\Str;

class LocalHost extends MachineInstance
{
    public function createBash(string $templateFileName, string $bashFileName, array $variables): FileHandler
    {
        $tmpl = KLAHVIK_PATH.'src/bashTemplates/'.$templateFileName;
        if (!file_exists($tmpl)) {
            Console::error("bash template('$tmpl') does not exist");
        }
        $content = file_get_contents($tmpl);
        foreach ($variables as $name => $value) {
            $content = str_replace('${'.$name.'}', $value, $content);
        }

        $bashFile = $this->tmpFile($bashFileName);
        $bashFile->removeIfExists();
        $bashFile->put($content);

        return $bashFile;
    }

    protected function getProcessCommand(string|array $command): string
    {
        $commandString = implode(PHP_EOL, (array)$command);
        $delimiter = 'EOF-KLAHVIK-LOCAL-CMD';

        return "sh << $delimiter".PHP_EOL
            .$commandString.PHP_EOL
            .$delimiter;
    }

    public function execute(string|array $commands): string
    {
        $res = [];
        foreach ((array)$commands as $cmd) {
            $output = null;
            exec($cmd, $output);
            $res[] = $output;
        }

        return implode("\n", $res);
    }
}