<?php

namespace Infira\Klahvik;


use Infira\Utils\Dir;

class Klahvik
{
	public static function error(string $message)
	{
		throw new \Exception($message);
	}
	
	public static function fixPath(string $path)
	{
		$ex   = explode('/', $path);
		$last = end($ex);
		//folder name validation
		if (preg_match('/^[^\s^\x00-\x1f\\\\?*:"";<>|\/.][^\x00-\x1f\\\\?*:"";<>|\/]*[^\s^\x00-\x1f\\\\?*:"";<>|\/.]+$/', $last))
		{
			return $path;
		}
		
		return Dir::fixPath($path);
	}
}