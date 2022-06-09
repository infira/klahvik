<?php

namespace Infira\Klahvik\config;

class MainConfig extends Manager
{
	public function __construct(array $realConfig)
	{
		$configConfig = [
			'localTmpPath' => 'path',
			'db'           => '\\Infira\Klahvik\config\Db',
			'vagrant'      => '\\Infira\Klahvik\config\Server',
			'local'        => '\\Infira\Klahvik\config\LocalConfig',
			'docker'       => '\\Infira\Klahvik\config\Docker',
			'clients'      => function (array $clients)
			{
				foreach ($clients as $client => $config) {
					$config['db']     = $this->getDb()->getMerged($config['db']);
					$clients[$client] = new Client($config, $client, "$this->instance/$client");
				}
				
				return $clients;
			},
		];
		parent::__construct('config', '', $configConfig, $realConfig);
	}
	
	public function getDb(): Db
	{
		return $this->get('db');
	}
	
	public function getVagrant(): Server
	{
		return $this->get('vagrant');
	}
	
	public function getLocal(): LocalConfig
	{
		return $this->get('local');
	}
	
	public function getDocker(): Docker
	{
		return $this->get('docker');
	}
	
	public function getClient(string $client): Client
	{
		$clients = $this->getClients();
		if (!isset($clients[$client])) {
			$this->error('clients', "client('$client') does not exist");
		}
		
		return $clients[$client];
	}
	
	public function getClients(): array
	{
		return $this->get('clients');
	}
}