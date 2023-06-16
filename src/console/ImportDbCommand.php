<?php

namespace Infira\Klahvik\console;


use Infira\Console\ConsoleRuntimeException;
use Infira\Klahvik\config\DbConfig;
use Infira\Klahvik\config\Models\DbProject;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Wolo\File\FileHandler;

/**
 * @property DbConfig $config;
 */
class ImportDbCommand extends Command
{
    public function __construct(string $client)
    {
        parent::__construct('db', $client);
    }

    public function configure(): void
    {
        parent::configure();
        $this->config = $this->clientConfig->getDb();
        $this->addArgument(
            'project',
            InputArgument::IS_ARRAY,
            'What client to use',
            $this->config->getProjectNames(),
            function (CompletionInput $input) {
                //$currentValue = $input->getCompletionValue();
                return $this->config->getProjectNames();
            }
        );
        $this->addOption('localDb', 'l', InputOption::VALUE_OPTIONAL, 'local db name', null);
        $this->addOption('branch', 'b', InputOption::VALUE_OPTIONAL, 'Into what branch', 'master');
        $this->addOption('force', 'f', InputOption::VALUE_NONE);
    }

    public function runCommand(): void
    {
        $projects = $this->input->getArgument('project');
        $projects = $projects[0] === 'all' ? $this->config->getProjectNames() : $projects;
        foreach ($projects as $argProject) {
            $project = $this->config->project($argProject);
            $this->console->region("importing project('$project')", function () use ($project) {
                $this->downloadDb($project);
                $this->importDb($project);
                $this->runTasks($project);
            });
            $this->console->nl();
        }
    }

    protected function downloadDb(DbProject $project): void
    {
        $db = $project->db->toString();
        $dataFile = $this->dataFile($db);
        if ($this->input->getOption('force')) {
            $dataFile->removeIfExists();
            $this->structureFile($db)->removeIfExists();
        }
        elseif ($dataFile->exists()) {
            return;
        }
        $this->remote->task("fetching databaase <comment>$db</comment> from server {name}", function () use ($db) {
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

            $upload = $this->remote->upload($dumpBash, $remoteBash)->runTask('uploading bash file');
            if (!$upload->isSuccessful()) {
                $upload->speakFailedStatus();

                return;
            }
            $upload->speakDone();
            $dumpBash->remove();
            $dump = $this->remote->execute("sh $remoteBash $db $dumpBash", "dumping database");
            if (!$dump->isSuccessful()) {
                $dump->speakFailedStatus();

                return;
            }
            $dump->speakDone();
            $this->remote->deleteFile($remoteBash);

            $tarFileName = "$db.tar.gz";
            $localTarFile = $this->local->tmpFile($tarFileName);
            $downloadTar = $this->remote
                ->download(
                    $this->remote->tmpPath($tarFileName),
                    $localTarFile
                )
                ->runTask("downloading $tarFileName");

            if (!$downloadTar->isSuccessful()) {
                $downloadTar->speakFailedStatus();

                return;
            }
            $downloadTar->speakDone();
            $this->remote->deleteFile(
                $this->remote->tmpPath("$db.structure.sql"),
                $this->remote->tmpPath("$db.data.sql"),
                $this->remote->tmpPath("$db.tar.gz"),
            );

            $unpack = $this->local
                ->process(
                    sprintf(
                        'tar -xvf %s -C %s',
                        $localTarFile->toString(),
                        $this->local->tmpPath()
                    )
                )
                ->voidRunError()->runTask("unpacking tar");
            if (!$unpack->isSuccessful()) {
                $unpack->speakFailedStatus();

                return;
            }
            if (!$this->structureFile($db)->exists() || !$this->dataFile($db)->exists()) {
                throw new ConsoleRuntimeException('SQL files were not found');
            }
            $unpack->speakDone();
            $localTarFile->remove();
        });
    }

    protected function importDb(DbProject $project): void
    {
        $fromDb = $project->db->toString();
        $branch = $this->input->getOption('branch');
        $db = $this->input->getOption('localDb') ?: $this->config->getLocalName($branch, $project);

        $this->local->task("importing to {name}", function () use ($db, $fromDb) {
            $structureFile = $this->structureFile($fromDb);
            $dataFile = $this->dataFile($fromDb);

            $createDb = $this->docker->mysqlQuery([
                'DROP DATABASE IF EXISTS '.$db,
                'CREATE DATABASE '.$db.' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            ])->runTask("creating $db");

            if (!$createDb->isSuccessful()) {
                $createDb->speakFailedStatus();

                return;
            }
            $createDb->speakDone();

            $import = $this->docker->mysqlQueryFromFile($db, [$structureFile, $dataFile])->runTask("importing $db");
            if (!$import->isSuccessful()) {
                $import->speakFailedStatus();
            }
            $import->speakDone();
        });
    }

    private function runTasks(DbProject $project): void
    {
        $tasks = $project->tasks();
        if ($tasks->isEmpty()) {
            return;
        }
        $tasks->each(function (string $task) use ($project) {
            $this->console->miniRegion(
                "running project($project) task($task)",
                fn() => $this->local->execute($task),
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

    private function structureFile(string $db): FileHandler
    {
        return $this->local->tmpFile("$db.structure.sql");
    }

    private function dataFile(string $db): FileHandler
    {
        return $this->local->tmpFile("$db.data.sql");
    }
}