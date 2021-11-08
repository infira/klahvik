<?php

namespace Infira\Klahvik\console\gws;

class PhpStorm extends \Infira\Klahvik\console\PhpStorm
{
	use RemoteConfig;
	
	protected ?string $name = 'gws';
	
	protected function configureMethod()
	{
		$this->opt('repoUrl', 'git@bitbucket.org:infira/gws.git');
		$this->opt('clonePath', '/Users/gentaliaru/ws/git/gws/branches/gws/');
		$this->opt('branchPrefix', 'gar');
	}
}