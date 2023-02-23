<?php

namespace Infira\Klahvik\config;

use Illuminate\Support\Collection;
use Infira\Console\Console;
use Wolo\File\Path;

class ConfigCollection extends Collection
{
	public function getReal($key, $default = null)
	{
		return parent::get($key, $default);
	}
	
	public function get($key, $default = null)
	{
		if (!$this->has($key)) {
			$this->error($key, 'does not exist');
		}
		
		return parent::get($key, $default);
	}
	
	public function getString(string $key): string
	{
		return $this->get($key);
	}
	
	public function getArray(string $key): array
	{
		return $this->get($key);
	}
	
	/**
	 * @param string $key
	 * @param string $collectionClass
	 * @return ConfigCollection
	 */
	public function collection(string $key, string $collectionClass = ConfigCollection::class)
	{
		return new $collectionClass($this->getArray($key));
	}
	
	public function getPath(string $key): string
	{
		$value = $this->get($key);
		if (!is_string($value)) {
			$this->error($key, 'must be path');
		}
		
		return Path::slash($value);
	}
	
	public function exists(string $key): bool
	{
		return $this->has($key);
	}
	
	protected function error(string $key, string $message): void
	{
		Console::error("ConfigManager('" . static::class . "') says: key('$key') $message");
	}
}