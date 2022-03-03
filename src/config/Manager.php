<?php

namespace Infira\Klahvik\config;

use Wolo\File\Path;
use Infira\Error\Error;
use Infira\console\Console;

abstract class Manager
{
	private array    $config = [];
	protected string $instance;
	const NOT_SET = '__NOT_SET_';
	
	public function __construct(string $instance, string $parentInstance, array $keyConfig, array $realConfig)
	{
		$this->instance = $parentInstance ? "$parentInstance/$instance" : $instance;
		$this->config   = $realConfig;
		$this->parseConfig($keyConfig, $this->config);
	}
	
	private function parseConfig(array $keyConfig, array &$config)
	{
		foreach ($keyConfig as $key => $parser) {
			$config[$key] = $this->parseConfigKey($key, $parser, $config[$key] ?? self::NOT_SET);
		}
	}
	
	private function parseConfigKey(string $key, $parser, $value)
	{
		$value      = $this->parserValue($value);
		$isValueSet = $value !== self::NOT_SET;
		if (is_string($parser) and str_starts_with($parser, '??')) {
			$parser = substr($parser, 2);
			
			//??stringPath:{klahvikPath}tmp
			$else = null;
			if (strpos($parser, ':')) {
				$ex     = explode(':', $parser);
				$else   = $ex[1];
				$parser = $ex[0];
			}
			
			if (!$isValueSet and $else === null) {
				return null;
			}
			if (!$isValueSet and $else !== null) {
				return $this->parseConfigKey($key, "??$parser", $else);
			}
			
			return $this->parseConfigKey($key, $parser, $value);
		}
		elseif (is_string($parser) and $parser[0] == '\\' and $isValueSet) {
			return new $parser($value, $this->instance);
		}
		elseif ($parser === 'array' and !is_array($value) and $isValueSet) {
			$this->error($key, 'must be string');
		}
		elseif ($parser === 'int' and !is_int($value) and $isValueSet) {
			$this->error($key, 'must be integer');
		}
		elseif ($parser === 'string' and !is_string($value) and $isValueSet) {
			$this->error($key, 'must be string');
		}
		elseif ($parser === 'path' and $isValueSet) {
			if (!is_string($value) and !is_dir($value)) {
				$this->error($key, 'must be exising path');
			}
			
			return Path::slash($value);
		}
		elseif ($parser === 'stringPath') {
			if (!is_string($value)) {
				$this->error($key, 'must be path');
			}
			if ($value[0] != '/') {
				$this->error($key, 'must be absolute path');
			}
			
			return Path::slash($value);
		}
		elseif (is_callable($parser)) {
			return $parser($value, $key);
		}
		if (!$isValueSet) {
			$this->error($key, 'is mandatory');
		}
		
		return $value;
	}
	
	private function parserValue($value)
	{
		if (is_string($value) and preg_match('/\[(.*)\]/m', $value, $matches)) {
			return str_replace($matches[0], $this->config[$matches[1]], $value);
		}
		
		return $value;
	}
	
	protected function get(string $name)
	{
		if (!array_key_exists($name, $this->config)) {
			$this->error($name, 'does not exist');
		}
		
		return $this->config[$name];
	}
	
	protected function error(string $key, string $message)
	{
		Error::addDebug('configTrace', $this->instance);
		Console::error("ConfigManager('$this->instance') says: key('$key') $message");
	}
	
	public function getConfigs(): array
	{
		return $this->config;
	}
	
	public function getMerged(array $merge): array
	{
		foreach ($this->config as $key => $conf) {
			if (array_key_exists($key, $merge)) {
				$merge[$key] = is_array($conf) ? array_merge($conf, $merge[$key]) : $merge[$key];
				continue;
			}
			$merge[$key] = $conf;
		}
		
		return $merge;
	}
	
}