<?php

namespace Infira\Klahvik\console;

use Infira\Console\Machine\DockerImage;
use Infira\Console\Machine\SshServer;
use Infira\Klahvik\config\ClientConfig;
use Infira\Klahvik\config\Config;
use Infira\Klahvik\helper\LocalHost;
use Infira\Klahvik\helper\RemoteServer;

class Command extends \Infira\Console\Command
{
    protected SshServer $remote;
    protected LocalHost $local;
    protected DockerImage $docker;
    protected ClientConfig $clientConfig;

    public function __construct(string $command, string $client)
    {
        if ($client && $command) {
            $this->clientConfig = Config::getClient($client);
            parent::__construct("$command:$client");
        }
        else {
            parent::__construct($command);
        }
    }


    protected function configureExecute()
    {
        $this->configureRemote();
        $localConfig = Config::getLocal()->toArray();
        $localConfig['tmpPath'] = Config::getLocal()->getTmpPath();
        $this->local = new LocalHost($this->output, $localConfig);
        $dockerConfig = Config::getDocker()->toArray();
        $dockerConfig['tmpPath'] = Config::getDocker()->getTmpPath();
        $this->docker = new DockerImage($this->output, $dockerConfig, 'mysql.docker');

        $serverConfig = $this->clientConfig->getServer()->toArray();
        $serverConfig['tmpPath'] = $this->clientConfig->getServer()->getTmpPath();
        $this->remote = new SshServer(
            $this->output,
            $serverConfig,
            $this->local,
            $this->clientConfig->getServer()->getHost()
        );
        $this->configureMethod();
    }

    protected function configureMethod() {}

    protected function beforeExecute() {}

    protected function afterExecute()
    {
        //void
    }

    protected function configureRemote() {}
}