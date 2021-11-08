<?php

namespace Infira\Klahvik\console\gws;

class Db extends \Infira\Klahvik\console\Db
{
	use RemoteConfig;
	
	protected ?string $name = 'gws';
	
	public function __construct()
	{
		parent::__construct([
			'garmin'      => 'd79590_lvgrm',
			'intra'       => 'd79590_livint',
			'gopro'       => 'd79590_lvgpr',
			'gps24'       => 'd79590_lvgps24',
			'gpseesti'    => 'd79590_gpseesti',
			'luxify'      => 'd79590_luxify',
			'meremaailm'  => 'd79590_lvmm',
			'miiego'      => 'd79590_miiego',
			'nutistuudio' => 'd79590_lvnut',
			'oakley'      => 'd79590_oakley',
		], 'gws');
	}
}