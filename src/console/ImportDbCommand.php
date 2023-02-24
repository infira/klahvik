<?php

namespace Infira\Klahvik\console;


use Infira\Console\Console;
use Infira\Klahvik\config\Config;
use Infira\Klahvik\config\DbConfig;
use Infira\Klahvik\config\Models\DbProject;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Wolo\File\File;
use Wolo\File\FileHandler;

/**
 * @property DbConfig $config;
 */
class ImportDbCommand extends Command
{
    public function __construct(string $client)
    {
        parent::__construct('db', $client);
        $this->config = $this->clientConfig->getDb();
    }

    public function configure(): void
    {
        $this->addArgument('project', InputArgument::IS_ARRAY, 'What project to download', ['all']);
        $this->addOption('localDb', 'l', InputOption::VALUE_OPTIONAL, 'local db name', null);
        $this->addOption('branch', 'b', InputOption::VALUE_OPTIONAL, 'Into what branch', 'master');
        $this->addOption('force', 'f', InputOption::VALUE_NONE);
        $this->addOption('del');
    }

    public function runCommand(): void
    {
        $projects = $this->input->getArgument('project');
        $projects = $projects[0] === 'all' ? $this->config->getProjectNames() : $projects;
        foreach ($projects as $argProject) {
            $project = $this->config->project($argProject);
            Console::region("importing project('$project')", function () use ($project) {
                $this->downloadRemoteDb($project);
                $this->importToDocker($project);
                $this->runTasks($project);
            });
            Console::nl();
        }
    }

    protected function downloadRemoteDb(DbProject $project): void
    {
        $db = $project->db->toString();
        $tarFile = $this->local->tmpFile("$db.tar.gz");
        if (!$this->input->getOption('force') && $tarFile->exists()) {
            return;
        }
        $this->remote->task("fetching databaase <comment>$db</comment> from server", function () use ($db, $tarFile) {
            $tarFile->removeIfExists();
            $tmpPath = $this->remote->tmpPath();
            $bashVars = [
                'db' => $db,
                'tempPath' => $tmpPath,
            ];
            $mysqlArguments = [];
            if ($user = $this->config->getUser()) {
                $mysqlArguments[] = "-u $user";
            }
            if ($pass = $this->config->getPass()) {
                $mysqlArguments[] = "-p $pass";
            }
            if ($host = $this->config->getHost()) {
                $mysqlArguments[] = "-h $host";
            }
            if ($groupSuffix = $this->config->groupSuffix()) {
                $mysqlArguments[] = "--defaults-group-suffix=$groupSuffix";
            }
            $dumpBash = $this->createDumpDbBash($bashVars, $this->config->getVoidDataDumpTables(), $mysqlArguments);
            $remoteBash = $this->remote->tmpPath('dumpDb.sh');

            $this->remote->uploadProcess($dumpBash, $remoteBash)->speak('uploading bash file');
            debug($remoteBash);exit;
            $dumpBash->remove();
            $this->remote->process("sh $remoteBash $db $dumpBash")->speak("dumping database");
            $this->remote->deleteFile($remoteBash);

            $this->remote
                ->downloadFileProcess(
                    $this->remote->tmpPath("$db.tar.gz"),
                    Config::getLocalTmpPath()
                )
                ->speak("downloading $db.tar.gz");

            $this->remote->deleteFile(
                $this->remote->tmpPath("$db.structure.sql"),
                $this->remote->tmpPath("$db.data.sql"),
                $this->remote->tmpPath("$db.tar.gz"),
            );
        });
    }

    protected function importToDocker(DbProject $project): void
    {
        $deleteDumpFiles = $this->input->getOption('del');
        $fromDb = $project->db->toString();
        $branch = $this->input->getOption('branch');
        $db = $this->input->getOption('localDb') ?: $this->config->getLocalName($branch, $project);

        $this->local->task("importing to docker", function () use ($db, $fromDb, $deleteDumpFiles) {
            $this->local->process(
                sprintf(
                    ' tar -xvf %s -C %s',
                    Config::getLocalTmpPath("$fromDb.tar.gz"),
                    Config::getLocalTmpPath()
                )
            )->speak("unpacking tar");

            $this->docker->executeMysql('DROP DATABASE IF EXISTS '.$db)->speak("droping old $db");
            $this->docker->executeMysql('CREATE DATABASE '.$db.' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')->speak("creating $db");
            $structureFile = $this->docker->tmpPath("$fromDb.structure.sql");
            $dataFile = $this->docker->tmpPath("$fromDb.data.sql");
            $this->docker->importSqlFromFile($db, $structureFile)->speak("importing $structureFile");
            $this->docker->importSqlFromFile($db, $dataFile)->speak("importing $dataFile");
            $this->local->deleteFile($structureFile, $dataFile);
            if ($deleteDumpFiles) {
                $this->local->deleteFile(Config::getLocalTmpPath("$fromDb.tar.gz"));
            }
        });
    }

    private function runTasks(DbProject $project): void
    {
        $tasks = $project->tasks();
        if ($tasks->isEmpty()) {
            return;
        }
        $tasks->each(function (string $task) use ($project) {
            Console::miniRegion(
                "running project($project) task($task)",
                fn() => $this->local->process($task)->speak(),
                20
            );
        });
    }

    public function createDumpDbBash(array $variables, array $ignoreTables, $mysqlArguments = []): FileHandler
    {
        $variables['IGNORE_DATA_TABLE_STRING'] = [];
        foreach ($ignoreTables as $table) {
            $variables['IGNORE_DATA_TABLE_STRING'][] = '--ignore-table="'.$variables['db'].'.'.$table.'"';
        }
        $variables['IGNORE_DATA_TABLE_STRING'] = implode(' ', $variables['IGNORE_DATA_TABLE_STRING']);

        $variables['mysqlArguments'] = implode(' ', $mysqlArguments);

        return $this->local->createBash('dumpDb.sh.template', 'dumpDb.sh', $variables);
    }
}