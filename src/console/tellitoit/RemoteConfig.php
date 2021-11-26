<?php

namespace Infira\Klahvik\console\tellitoit;

/**
 * @mixin \Infira\Klahvik\console\Command
 */
trait RemoteConfig
{
	public function configureRemote(): void
	{
		$this->opt('REMOTE_USER', 'virt44836');
		$this->opt('REMOTE_HOST', '217.146.68.129');
		$this->opt('REMOTE_KLAHVIK_PATH', '/data01/virt44836/domeenid/www.tellitoit.ee/klahvik');
		$this->opt('REMOTE_TMP_PATH', '/data01/virt44836/domeenid/www.tellitoit.ee/klahvik');
	}
}