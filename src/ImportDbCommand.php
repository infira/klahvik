<?php

namespace Infira\Klahvik;

use Infira\Console\Exceptions\ConsoleRuntimeException;
use Infira\Klahvik\Config\DbConfig;
use Infira\Klahvik\Config\DbProject\DbProject;
use Infira\Klahvik\Config\DbProject\DbProjectCollection;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Wolo\File\FileHandler;

class ImportDbCommand extends Command
{
    private DbConfig $config;
    private DbProjectCollection $projects;

    public function __construct(string $client)
    {
        parent::__construct('db', $client);
    }

    public function configure(): void
    {
        parent::configure();
        $this->config = $this->clientConfig->getDb();

        //debug(['$this->config' => $this->config]);exit;
        $this->projects = $this->config->projects();
        //debug(['projects.names' => $this->projects->names()]);exit;
        //debug(['projects' => $this->projects]);exit;

        $this->addArgument(
            'project',
            InputArgument::IS_ARRAY,
            'What client to use',
            $this->projects->names(),
            //$currentValue = $input->getCompletionValue();
            static fn(CompletionInput $input) => $this->projects->names()
        );
        $this->addOption('localDb', 'l', InputOption::VALUE_OPTIONAL, 'local db name', null);
        $this->addOption('branch', 'b', InputOption::VALUE_OPTIONAL, 'Into what branch', 'master');
        $this->addOption('force', 'f', InputOption::VALUE_NONE);
    }

    public function runCommand(): void
    {
        $projects = (array)$this->input->getArgument('project');
        $projects = $projects[0] === 'all' ? $this->projects->names() : $projects;
        foreach ($projects as $argProject) {
            if (!$this->projects->has($argProject)) {
                throw new ConsoleRuntimeException("project('$argProject') does not exist");
            }
            $project = $this->projects->get($argProject);
            $this->console->region("importing project('{$project}')", function () use ($project) {
                $this->downloadDb($project);
                $this->importDb($project);
            });

            $this->console->nl();
        }
    }

    protected function downloadDb(DbProject $project): void
    {
        $db = $project->getRemoteDbName();
        $dataFile = $this->dataFile($db);
        if ($this->input->getOption('force')) {
            $dataFile->removeIfExists();
            $this->structureFile($db)->removeIfExists();
        }
        else if ($dataFile->exists()) {
            return;
        }
        $regionTitle = "fetching database <comment>$db</comment> from server <name>{$this->remote->getHostOrName()}</name>";
        $this->console->miniRegion($regionTitle, function () use ($db) {
            $tmpPath = $this->remote->tempPath();
            $bashVars = [
                'db' => $db,
                'tempPath' => $tmpPath,
            ];
            $bashVars['mysqlArguments'] = [];
            if ($user = $this->config->getUser()) {
                $bashVars['mysqlArguments'][] = "-u $user";
            }
            if ($pass = $this->config->getPass()) {
                $bashVars['mysqlArguments'][] = "-p $pass";
            }
            if ($host = $this->config->getHost()) {
                $bashVars['mysqlArguments'][] = "-h $host";
            }
            if ($groupSuffix = $this->config->groupSuffix()) {
                $bashVars['mysqlArguments'][] = "--defaults-group-suffix=$groupSuffix";
            }

            if ($configArguments = $this->config->mysqlArguments()) {
                foreach ($configArguments as $name => $value) {
                    $bashVars['mysqlArguments'][] = "$name=$value";
                }
            }

            $dumpBash = $this->createDumpDbBash(
                $bashVars,
                $this->config->getVoidDataDumpTables()->all()
            );
            $remoteBash = $this->remote->tempPath('dumpDb.sh');
            $tarFileName = "$db.tar.gz";
            $localTarFile = $this->local->tempFile($tarFileName);
            $remoteTarFile = $this->remote->tempPath($tarFileName);

            $this->console->run([
                $this->local->upload($dumpBash, $this->remote->getRSyncPath($remoteBash))
                    ->name("uploading a bash file to(<name>{$this->remote->getHostOrName()}</name>)"),
                //remove local bash file after upload
                static fn() => $dumpBash->remove(),
                //execute remote bash file to dump database
                $this->remote->process([
                    "sh $remoteBash $db $dumpBash",
                    "rm -f $dumpBash" //remove remote bash file
                ])->name("dumping database <name>$db</name>"),

                $this->local->downloadFile(
                    $this->remote->getRSyncPath($remoteTarFile),
                    $localTarFile
                )->name("downloading <name>$tarFileName</name>"),

                //delete tar file from server
                $this->remote->deleteFile($this->remote->tempPath($tarFileName))
                    ->name("deleting <name>$tarFileName</name>"),

                //unpack tar file
                $this->local
                    ->process($command = sprintf(
                        'tar -xf %s -C %s -v',
                        $localTarFile->toString(),
                        $this->local->tempPath()
                    ))
                    //->setTimeout(null)
                    ->voidDIsplayRuntimeErrors()
                    ->voidExitCodesAsErrors(143)// 143 = terminated
                    ->name("unpacking <name>$tarFileName</name>"),
            ]);

            if (!$this->structureFile($db)->exists() || !$this->dataFile($db)->exists()) {
                throw new ConsoleRuntimeException('SQL files were not found');
            }
            $localTarFile->remove();
        });
    }

    protected function importDb(DbProject $project): void
    {
        $fromDb = $project->getRemoteDbName();
        $branch = $this->input->getOption('branch');
        $db = $this->input->getOption('localDb') ?: $project->getLocalDbName($branch, $project);
        $dockerName = $this->docker->getName();
        $serverHost = $this->remote->config->getHost();

        $regionTitle = "importing from <name>$serverHost</name>@<name>$fromDb</name> to <name>$dockerName</name>@<name>$db</name>";
        $this->console->miniRegion($regionTitle, function () use ($db, $fromDb) {

            $structureFile = $this->structureFile($fromDb);
            $structureFilename = basename($structureFile);
            $dataFile = $this->dataFile($fromDb);
            $dataFilename = basename($dataFile);

            $this->console->run([
                $this->docker->sqlQuery('DROP DATABASE IF EXISTS '.$db)->name("drop $db"),
                $this->docker->sqlQuery('CREATE DATABASE '.$db.' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')->name("create $db"),
                //importing structure & data
                $this->docker->sqlQueryFromFile($db, $structureFile)
                    ->name("importing structure <name>$structureFilename</name>"),
                $this->docker->sqlQueryFromFile($db, $dataFile)
                    ->name("importing data <name>$dataFilename</name>")
            ]);
        });
    }

    public function createDumpDbBash(array $variables, array $ignoreTables): FileHandler
    {
        $variables['IGNORE_DATA_TABLE_STRING'] = [];
        foreach ($ignoreTables as $table) {
            $variables['IGNORE_DATA_TABLE_STRING'][] = '--ignore-table="'.$variables['db'].'.'.$table.'"';
        }
        $variables['IGNORE_DATA_TABLE_STRING'] = implode(' ', $variables['IGNORE_DATA_TABLE_STRING']);

        if (!array_key_exists('mysqlArguments', $variables)) {
            $variables['mysqlArguments'] = [];
        }

        $variables['mysqlArguments'] = implode(' ', $variables['mysqlArguments']);

        return $this->local->createBash('dumpDb.sh.template', 'dumpDb.sh', $variables);
    }

    private function structureFile(string $db): FileHandler
    {
        return $this->local->tempFile("$db.structure.sql");
    }

    private function dataFile(string $db): FileHandler
    {
        return $this->local->tempFile("$db.data.sql");
    }
}