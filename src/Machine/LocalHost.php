<?php

namespace Infira\Klahvik\Machine;

use Infira\Console\Exceptions\ConsoleRuntimeException;
use Infira\Klahvik\Machine\Config\LocalHostConfig;
use Infira\Klahvik\Config\Klahvik;
use Wolo\File\FileHandler;

/**
 * @property-read LocalhostConfig $config
 */
class LocalHost extends \Infira\Console\Machine\LocalHost
{
    public function createBash(string $templateFileName, string $basFilename, array $variables): FileHandler
    {
        $tmpl = Klahvik::getKlahvikPath('src/bashTemplates', $templateFileName);
        if (!file_exists($tmpl)) {
            throw new ConsoleRuntimeException("bash template('$tmpl') does not exist");
        }
        $content = file_get_contents($tmpl);
        foreach ($variables as $name => $value) {
            $content = str_replace('${'.$name.'}', $value, $content);
        }

        $bashFile = $this->tempFile($basFilename);
        $dirname = dirname($bashFile->path());

        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
        $bashFile->removeIfExists();
        $bashFile->put($content);

        return $bashFile;
    }
}