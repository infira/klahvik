<?php

namespace Infira\Klahvik\console\klavis;

class Db extends \Infira\Klahvik\console\Db
{
	use RemoteConfig;
	
	protected ?string $name = 'klavis';
	
	public function __construct()
	{
		parent::__construct([
			'kis' => 'd79874_kislive',
			'vin' => 'd79874_vinlive',
		], 'klavis');
	}
}