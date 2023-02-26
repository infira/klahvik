<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Console;
use Wolo\File\FileHandler;

class LocalHost extends \Infira\Console\Machine\LocalHost
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
}