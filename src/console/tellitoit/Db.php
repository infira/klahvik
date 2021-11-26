<?php

namespace Infira\Klahvik\console\tellitoit;

class Db extends \Infira\Klahvik\console\Db
{
	use RemoteConfig;
	
	protected ?string $name = 'tt';
	
	public function __construct()
	{
		parent::__construct([
			'api' => 'd45504sd83836',
		], 'tellitoit');
	}
}