<?php

namespace Infira\Klahvik\helper;

use Infira\Klahvik\console\Command;

class Local extends MachineInstance
{
	public function __construct(Command &$cmd)
	{
		parent::__construct('localhost', $cmd);
	}
	
	public function tmp(string $path = ''): string
	{
		return $this->cmd->opt('LOCAL_TMP_PATH') . $path;
	}
}