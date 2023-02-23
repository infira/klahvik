<?php

namespace Infira\Klahvik\helper;

use Infira\Console\Console;
use Infira\Console\Process;
use Infira\Klahvik\config\Config;
use Wolo\File\File;
use Wolo\File\Path;
use Wolo\Str;

class Local extends MachineInstance
{
    public function createBash(string $templateFileName, string $bashFileName, array $variables): string
    {
        $tmpl = KLAHVIK_PATH.'src/bashTemplates/'.$templateFileName;
        if (!file_exists($tmpl)) {
            Console::error("bash template('$tmpl') does not exist");
        }
        $content = file_get_contents($tmpl);
        foreach ($variables as $name => $value) {
            $content = str_replace('${'.$name.'}', $value, $content);
        }
        $bash = Config::getLocalTmpPath($bashFileName);
        File::removeIfExists($bash);
        File::put($bash, $content);

        return $bash;
    }

    public function createDumpDbBash(array $variables, array $ignoreTables, $mysqlArguments = []): string
    {
        $variables['IGNORE_DATA_TABLE_STRING'] = [];
        foreach ($ignoreTables as $table) {
            $variables['IGNORE_DATA_TABLE_STRING'][] = '--ignore-table="'.$variables['db'].'.'.$table.'"';
        }
        $variables['IGNORE_DATA_TABLE_STRING'] = implode(' ', $variables['IGNORE_DATA_TABLE_STRING']);

        $variables['mysqlArguments'] = implode(' ', $mysqlArguments);

        return $this->createBash('dumpDb.sh.template', 'dumpDb.sh', $variables);
    }

    public function rsyncFolderProcess(string $server, string $src, string $destination): Process
    {
        $src = Path::slash($src);
        $destination = Path::slash($destination);
        if (!Str::endsWith($src, '*')) {
            $src .= '*';
        }

        return $this->rsync($server, $src, $destination);
    }

    public function rsync(string $server, string $src, string $destination): Process
    {
        return $this->process("rsync --timeout=0 -av --progress --del $server:$src $destination");
    }
}