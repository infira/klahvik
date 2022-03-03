<?php

namespace Infira\Klahvik\config;

use Wolo\File\Path;

class Config extends Manager
{
	public function __construct(array $realConfig)
	{
		$configConfig = [
			'localTmpPath' => 'path',
			'db'           => '\\Infira\Klahvik\config\Db',
			'vagrant'      => '\\Infira\Klahvik\config\Server',
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
	
	public function getLocalTmpPath(string $path = ''): string
	{
		return Path::join($this->get('localTmpPath'), $path);
	}
	
	public function getDb(): Db
	{
		return $this->get('db');
	}
	
	public function getVagrant(): Server
	{
		return $this->get('vagrant');
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