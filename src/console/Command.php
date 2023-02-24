<?php

namespace Infira\Klahvik\console;

use Infira\Klahvik\config\ClientConfig;
use Infira\Klahvik\config\Config;
use Infira\Klahvik\helper\DockerMachine;
use Infira\Klahvik\helper\LocalHost;
use Infira\Klahvik\helper\RemoteServer;

class Command extends \Infira\Console\Command
{
    protected RemoteServer $remote;
    protected LocalHost $local;
    protected DockerMachine $docker;
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
        $this->local = new LocalHost('localhost', Config::getLocal(), $this->output);
        $this->docker = new DockerMachine($this->output);

        $this->remote = new RemoteServer($this->clientConfig->getServer(), $this->local, $this->output);
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