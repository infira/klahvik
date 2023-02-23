<?php

namespace Infira\Klahvik\console;


use Infira\Console\Console;
use Infira\Klahvik\config\Config;
use Infira\Klahvik\config\DbConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Wolo\File\File;

/**
 * @property DbConfig $config;
 */
class Db extends Command
{
    public function __construct(string $client)
    {
        parent::__construct('db', $client);
        $this->config = $this->clientConfig->getDb();
    }

    public function configure(): void
    {
        $this->addArgument('project', InputArgument::OPTIONAL, 'What project to download', 'all');
        $this->addOption('localDb', 'l', InputOption::VALUE_OPTIONAL, 'local db name', null);
        $this->addOption('branch', 'b', InputOption::VALUE_OPTIONAL, 'Into what branch', 'master');
        $this->addOption('force', 'f');
        $this->addOption('del');
    }

    public function runCommand(): void
    {
        $projects = $this->input->getArgument('project');
        $branch = $this->input->getOption('branch');
        foreach (explode(',', $projects) as $project) {
            if (!$this->config->projectExists($project)) {
                Console::error("project project('$project') not found");
            }

            $loop = $project === 'all' ? $this->config->getProjectNames() : [$project];
            foreach ($loop as $lProject) {
                $this->import($lProject, $branch);
                Console::nl();
            }
        }
    }

    private function import(string $project, string $branch): void
    {
        $forceDownload = $this->input->getOption('force');
        $deleteLocalDump = $this->input->getOption('del');

        $localDB = $this->input->getOption('localDb') ?: $this->config->getLocalName($branch, $project);
        $liveDB = $this->config->getRemoteName($project);

        Console::region("importing project('$project')", function () use ($deleteLocalDump, $forceDownload, $localDB, $liveDB) {
            $structurePath = Config::getLocalTmpPath("$liveDB.tar.gz");
            if (!file_exists($structurePath) or $forceDownload) {
                $this->downloadRemoteDb($liveDB);
            }
            $this->importToDocker($localDB, $liveDB, $deleteLocalDump);
        });
    }

    protected function downloadRemoteDb(string $db): void
    {
        $this->remote->task("fetching databse <comment>$db</comment> from server", function () use ($db) {
            $this->local->deleteFile(Config::getLocalTmpPath("$db.tar.gz"));
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
            $dumpBash = $this->local->createDumpDbBash($bashVars, $this->config->getVoidDataDumpTables(), $mysqlArguments);
            $remoteBash = $this->remote->tmpPath('dumpDb.sh');

            $this->remote->uploadProcess($dumpBash, $remoteBash)->speak('uploading bash file');
            File::removeIfExists($dumpBash);
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

    protected function importToDocker(string $db, string $fromDb, bool $deleteDumpFiles = false): void
    {
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
}