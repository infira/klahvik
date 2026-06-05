<?php

namespace Infira\Klahvik;

use Infira\Klahvik\Config\ClientConfig;
use Infira\Klahvik\Config\Klahvik;
use Infira\Klahvik\Machine\DockerImage;
use Infira\Klahvik\Machine\LocalHost;
use Infira\Klahvik\Machine\SshServer;

class Command extends \Infira\Console\Command
{
    protected SshServer $remote;
    protected LocalHost $local;
    protected DockerImage $docker;
    protected ClientConfig $clientConfig;

    public function __construct(string $command, string $client)
    {
        if ($client && $command) {
            $this->clientConfig = Klahvik::getClient($client);
            parent::__construct("$command:$client");
        }
        else {
            parent::__construct($command);
        }
    }

    protected function configureExecute(): void
    {
        parent::configureExecute();
        $this->local = new LocalHost($this->console, Klahvik::getLocalhost());
        $this->docker = new DockerImage(
            $this->console,
            Klahvik::getDocker()
        );

        $this->remote = new SshServer(
            $this->console,
            $this->clientConfig->getServerConfig()
        );
        $this->configureMethod();
    }

    protected function configureMethod() {}

    protected function beforeExecute() {}

    protected function afterExecute()
    {
        //void
    }
}